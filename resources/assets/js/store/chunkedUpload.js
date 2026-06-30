// 分片上传 wrapper。调用方按 file.size >= CHUNK_THRESHOLD 自行决定走老接口还是这里。
// 后端 3 接口：upload/init → upload/chunk × N → upload/merge

import axios from 'axios'
import SparkMD5 from 'spark-md5'
import store from './index'
import { languageName } from '../language'

export const CHUNK_THRESHOLD = 10 * 1024 * 1024
export const CHUNK_SIZE = 5 * 1024 * 1024 // 必须与后端 ChunkUpload::CHUNK_SIZE 一致
const CONCURRENCY = 3
const RETRY_MAX = 3
const LOCAL_INDEX_PREFIX = 'chunked_upload:'
const LOCAL_INDEX_TTL = 24 * 3600 * 1000

// 业务错误（非网络），命中立即抛，不浪费重试次数
const NON_RETRYABLE_MSGS = new Set([
    '上传会话不存在或已过期',
    '上传会话归属错误',
    '分片序号超出范围',
    '分片大小不符合预期',
    '末尾分片大小不符合预期',
])

// hashing 0-5 / uploading 5-95 / merging 95-100
function mapProgress(stage, percent) {
    if (stage === 'hashing') return Math.round(percent * 0.05)
    if (stage === 'uploading') return 5 + Math.round(percent * 0.9)
    return 95 + Math.round(percent * 0.05)
}

/**
 * @param {Object} opts
 * @param {File}   opts.file
 * @param {'file_cabinet'|'dialog_file'|'image'|'generic_file'} opts.scene
 * @param {Object} [opts.sceneParams]  透传到后端 merge 阶段
 * @param {(percent:number, stage:'hashing'|'uploading'|'merging') => void} [opts.onProgress]
 * @param {(uploadId:string) => void} [opts.onStart]  init 拿到 upload_id 后回调，秒传命中不会触发；调用方可据此实现取消
 * @param {AbortSignal} [opts.signal]
 * @returns {Promise<Object>}  merge 返回 data（与该 scene 老接口对齐）
 */
export async function chunkedUpload({ file, scene, sceneParams = {}, onProgress, onStart, signal }) {
    const cb = typeof onProgress === 'function' ? onProgress : () => {}
    const progress = (stage, pct) => cb(mapProgress(stage, pct), stage)

    progress('hashing', 0)
    const hash = await calcMd5(file, signal, (loaded, total) => {
        progress('hashing', Math.min(99, Math.round((loaded / total) * 100)))
    })
    progress('hashing', 100)
    if (signal && signal.aborted) throw new Error('aborted')

    const initResp = await callJson('upload/init', {
        hash,
        size: file.size,
        name: file.name,
        scene,
        scene_params: sceneParams,
    }, signal)
    if (initResp.ret !== 1) {
        throw new Error(initResp.msg || '上传初始化失败')
    }
    if (initResp.data && initResp.data.done) {
        progress('uploading', 100)
        progress('merging', 100)
        return initResp.data
    }
    const { upload_id, chunk_size, chunk_count, received } = initResp.data
    saveLocalIndex(hash, upload_id, scene)
    if (typeof onStart === 'function') {
        try { onStart(upload_id) } catch (_e) { /* 调用方异常不阻断上传 */ }
    }

    const receivedSet = new Set((received || []).map(Number))
    const todo = []
    for (let i = 0; i < chunk_count; i++) {
        if (!receivedSet.has(i)) todo.push(i)
    }
    let uploadedCount = receivedSet.size
    const updateUploading = () => {
        progress('uploading', Math.min(99, Math.round((uploadedCount / chunk_count) * 100)))
    }
    updateUploading()

    // cursor 协作：每个 worker 抓下一个 index，避免分片到 worker 的预分配在失败时空闲
    let cursor = 0
    let firstError = null
    const workOne = async () => {
        while (true) {
            if (firstError) return
            if (signal && signal.aborted) {
                firstError = new Error('aborted')
                return
            }
            const myCursor = cursor++
            if (myCursor >= todo.length) return
            const idx = todo[myCursor]
            const start = idx * chunk_size
            const end = Math.min(start + chunk_size, file.size)
            const blob = file.slice(start, end)
            try {
                await uploadChunkWithRetry(upload_id, idx, blob, signal)
                uploadedCount++
                updateUploading()
            } catch (e) {
                if (!firstError) firstError = e
                return
            }
        }
    }
    const workerCount = Math.min(CONCURRENCY, todo.length || 1)
    await Promise.all(Array.from({ length: workerCount }, () => workOne()))
    if (firstError) throw firstError
    progress('uploading', 100)

    progress('merging', 0)
    const mergeResp = await callJson('upload/merge', { upload_id }, signal)
    if (mergeResp.ret !== 1) {
        throw new Error(mergeResp.msg || '合并失败')
    }
    progress('merging', 100)
    clearLocalIndex(hash)
    return mergeResp.data
}

async function uploadChunkWithRetry(uploadId, index, blob, signal) {
    let lastErr
    for (let attempt = 0; attempt <= RETRY_MAX; attempt++) {
        if (signal && signal.aborted) throw new Error('aborted')
        try {
            const fd = new FormData()
            fd.append('upload_id', uploadId)
            fd.append('index', String(index))
            fd.append('blob', blob)
            const resp = await axios.post(window.$A.apiUrl('upload/chunk'), fd, {
                headers: buildHeaders(),
                signal,
            })
            if (resp.data && resp.data.ret === 1) return resp.data.data
            lastErr = new Error((resp.data && resp.data.msg) || '分片上传失败')
        } catch (e) {
            if (signal && signal.aborted) throw e
            lastErr = e
        }
        if (NON_RETRYABLE_MSGS.has(lastErr.message)) throw lastErr
        // 指数退避 500ms / 1s / 2s
        if (attempt < RETRY_MAX) {
            await new Promise(r => setTimeout(r, 500 * Math.pow(2, attempt)))
        }
    }
    throw lastErr || new Error('分片重试耗尽')
}

async function callJson(path, data, signal) {
    const resp = await axios.post(window.$A.apiUrl(path), data, {
        headers: {
            ...buildHeaders(),
            'Content-Type': 'application/json',
        },
        signal,
    })
    return resp.data
}

function buildHeaders() {
    return {
        token: store.state.userToken,
        language: languageName,
        platform: window.$A.Platform,
        version: (window.systemInfo && window.systemInfo.version) || '0.0.1',
        fd: window.$A.getSessionStorageString('userWsFd'),
    }
}

// 主线程算 hash：Vite ?worker 在 dev/反代下 worker URL 走 document.baseURI → 主程序返回 HTML → MIME 报错。
// 实测 12MB ~200ms / 120MB ~2s / 1G ~20s，每片 setTimeout(0) 让渡保持 UI 刷新。
async function calcMd5(file, signal, onProg) {
    const spark = new SparkMD5.ArrayBuffer()
    const READ = 2 * 1024 * 1024
    for (let offset = 0; offset < file.size; offset += READ) {
        if (signal && signal.aborted) throw new Error('aborted')
        const end = Math.min(offset + READ, file.size)
        const buf = await file.slice(offset, end).arrayBuffer()
        spark.append(buf)
        onProg(end, file.size)
        await new Promise(r => setTimeout(r, 0))
    }
    return spark.end().toLowerCase()
}

function saveLocalIndex(hash, uploadId, scene) {
    try {
        const data = { upload_id: uploadId, scene, t: Date.now() }
        localStorage.setItem(LOCAL_INDEX_PREFIX + hash, JSON.stringify(data))
    } catch (_e) { /* localStorage 容量满 */ }
}

function clearLocalIndex(hash) {
    try {
        localStorage.removeItem(LOCAL_INDEX_PREFIX + hash)
    } catch (_e) { /* noop */ }
}

/**
 * 清理本机过期的续传索引（可在 store 启动时调一次）。
 */
export function purgeLocalIndex() {
    try {
        const now = Date.now()
        for (let i = localStorage.length - 1; i >= 0; i--) {
            const key = localStorage.key(i)
            if (!key || !key.startsWith(LOCAL_INDEX_PREFIX)) continue
            try {
                const v = JSON.parse(localStorage.getItem(key) || '{}')
                if (!v.t || now - v.t > LOCAL_INDEX_TTL) {
                    localStorage.removeItem(key)
                }
            } catch (_e) {
                localStorage.removeItem(key)
            }
        }
    } catch (_e) { /* noop */ }
}

export default chunkedUpload
