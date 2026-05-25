const fs = require('fs');
const {
    S3Client,
    PutObjectCommand,
    GetObjectCommand,
    CopyObjectCommand,
    DeleteObjectsCommand,
    ListObjectsV2Command,
} = require('@aws-sdk/client-s3');
const { Upload } = require('@aws-sdk/lib-storage');

const {
    R2_ACCESS_KEY_ID,
    R2_SECRET_ACCESS_KEY,
    R2_ENDPOINT,
    R2_BUCKET,
    R2_PUBLIC_URL,
} = process.env;

function r2Configured() {
    return !!(R2_ACCESS_KEY_ID && R2_SECRET_ACCESS_KEY && R2_ENDPOINT && R2_BUCKET);
}

function createR2Client() {
    return new S3Client({
        region: 'auto',
        endpoint: R2_ENDPOINT,
        credentials: {
            accessKeyId: R2_ACCESS_KEY_ID,
            secretAccessKey: R2_SECRET_ACCESS_KEY,
        },
    });
}

function contentTypeFor(name) {
    if (/\.ya?ml$/i.test(name)) return 'text/yaml';
    if (/\.json$/i.test(name)) return 'application/json';
    if (/\.md$/i.test(name)) return 'text/markdown; charset=utf-8';
    return 'application/octet-stream';
}

/** 流式上传本地文件，onProgress(loaded, total) */
async function uploadFile(client, localFile, key, onProgress) {
    const total = fs.statSync(localFile).size;
    const upload = new Upload({
        client,
        params: {
            Bucket: R2_BUCKET,
            Key: key,
            Body: fs.createReadStream(localFile),
            ContentType: contentTypeFor(key),
        },
    });
    if (onProgress) {
        upload.on('httpUploadProgress', (p) => onProgress(p.loaded || 0, total));
    }
    await upload.done();
}

/** 写入文本对象 */
async function putText(client, key, text) {
    await client.send(new PutObjectCommand({
        Bucket: R2_BUCKET,
        Key: key,
        Body: text,
        ContentType: contentTypeFor(key),
    }));
}

/** 读取文本对象，不存在返回 null */
async function getText(client, key) {
    try {
        const res = await client.send(new GetObjectCommand({ Bucket: R2_BUCKET, Key: key }));
        return await res.Body.transformToString();
    } catch (err) {
        if (err.name === 'NoSuchKey' || err.$metadata?.httpStatusCode === 404) return null;
        throw err;
    }
}

/** 桶内服务端复制（文件名为安全 ASCII，无需额外编码） */
async function copyObject(client, srcKey, destKey) {
    await client.send(new CopyObjectCommand({
        Bucket: R2_BUCKET,
        CopySource: `${R2_BUCKET}/${srcKey}`,
        Key: destKey,
        ContentType: contentTypeFor(destKey),
        MetadataDirective: 'REPLACE',
    }));
}

/** 列举 key；delimiter='/' 时仅返回该前缀下的根层对象（子目录归 CommonPrefixes，不返回） */
async function listKeys(client, prefix, delimiter) {
    const keys = [];
    let token;
    do {
        const res = await client.send(new ListObjectsV2Command({
            Bucket: R2_BUCKET,
            Prefix: prefix,
            Delimiter: delimiter,
            ContinuationToken: token,
        }));
        for (const o of res.Contents || []) keys.push(o.Key);
        token = res.IsTruncated ? res.NextContinuationToken : undefined;
    } while (token);
    return keys;
}

/** 批量删除（每批 1000） */
async function deleteKeys(client, keys) {
    for (let i = 0; i < keys.length; i += 1000) {
        const batch = keys.slice(i, i + 1000);
        if (!batch.length) continue;
        await client.send(new DeleteObjectsCommand({
            Bucket: R2_BUCKET,
            Delete: { Objects: batch.map((Key) => ({ Key })) },
        }));
    }
}

module.exports = {
    r2Configured,
    createR2Client,
    contentTypeFor,
    uploadFile,
    putText,
    getText,
    copyObject,
    listKeys,
    deleteKeys,
    R2_BUCKET,
    R2_PUBLIC_URL,
};
