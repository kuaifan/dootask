<template>
    <div @click="onCLick" class="markdown-body dialog-markdown" v-html="html"></div>
</template>

<script>
import '../../../../sass/pages/components/dialog-markdown/markdown.less'
import {MarkdownConver} from "../../../utils/markdown";
import {isDeepLinkId, openDeepLink} from "../../../components/AIAssistant/deep-links";

export default {
    name: "DialogMarkdown",
    props: {
        text: {
            type: String,
            default: ''
        },
        // 导航前回调（如关闭弹窗）
        beforeNavigate: {
            type: Function,
            default: null
        },
    },
    data() {
        return {
            mdi: null,
        }
    },

    mounted() {
        this.copyCodeBlock()
    },

    updated() {
        this.copyCodeBlock()
    },

    computed: {
        html({text}) {
            return MarkdownConver(text)
        }
    },

    methods: {
        copyCodeBlock() {
            const codeBlockWrapper = this.$el.querySelectorAll('.code-block-wrapper')
            codeBlockWrapper.forEach((wrapper) => {
                const copyBtn = wrapper.querySelector('.code-block-header__copy')
                const codeBlock = wrapper.querySelector('.code-block-body')
                if (copyBtn && codeBlock && copyBtn.getAttribute("data-copy") !== "click") {
                    copyBtn.setAttribute("data-copy", "click")
                    copyBtn.addEventListener('click', () => {
                        if (navigator.clipboard?.writeText)
                            navigator.clipboard.writeText(codeBlock.textContent ?? '')
                        else
                            this.copyContent({text: codeBlock.textContent ?? '', origin: true})
                    })
                }
            })
        },

        copyContent(options) {
            const props = {origin: true, ...options}

            let input

            if (props.origin)
                input = document.createElement('textarea')
            else
                input = document.createElement('input')

            input.setAttribute('readonly', 'readonly')
            input.value = props.text
            document.body.appendChild(input)
            input.select()
            if (document.execCommand('copy'))
                document.execCommand('copy')
            document.body.removeChild(input)
        },

        onCLick(e) {
            const target = e.target;
            if (target.tagName === 'A') {
                const href = target.getAttribute('href');
                if (href && href.startsWith('dootask://')) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.handleDooTaskLink(href);
                    return;
                }
            }
            this.$emit('click', e)
        },

        /**
         * 处理 dootask:// 协议链接
         * 格式: dootask://type/id 或 dootask://type/id1/id2
         * 文件链接支持: dootask://file/123 (数字ID) 或 dootask://file/OSwxLHY3ZlN2R245 (base64编码)
         * AI 建议链接: dootask://ai-apply/{type}/{task_id}/{msg_id} 或 dootask://ai-dismiss/...
         */
        handleDooTaskLink(href) {
            // AI 回复内联深链：dootask://link/<id> → 导航到目录目的地
            if (href.startsWith('dootask://link/')) {
                this.handleDeepLink(href);
                return;
            }
            // 优先处理 AI 建议链接（格式与其他类型不同）
            if (href.startsWith('dootask://ai-apply/')) {
                this.handleAiApply(href);
                return;
            }
            if (href.startsWith('dootask://ai-dismiss/')) {
                this.handleAiDismiss(href);
                return;
            }

            const match = href.match(/^dootask:\/\/(\w+)\/([^/]+)(?:\/(\d+))?$/);
            if (!match) {
                return;
            }

            const [, type, id, id2] = match;
            const isNumericId = /^\d+$/.test(id);
            const numId = isNumericId ? parseInt(id, 10) : null;
            const numId2 = id2 ? parseInt(id2, 10) : null;

            switch (type) {
                case 'task':
                    this.beforeNavigate?.();
                    this.$store.dispatch('openTask', { id: (numId2 && numId2 > 0) ? numId2 : numId });
                    break;

                case 'project':
                    this.beforeNavigate?.();
                    this.goForward({ name: 'manage-project', params: { projectId: numId } });
                    break;

                case 'file':
                    if (isNumericId) {
                        // 数字ID：跳转到文件列表并高亮
                        this.beforeNavigate?.();
                        this.goForward({ name: 'manage-file', params: { folderId: 0, fileId: null, shakeId: numId } });
                        this.$store.state.fileShakeId = numId;
                        setTimeout(() => {
                            this.$store.state.fileShakeId = 0;
                        }, 600);
                    } else {
                        // 非数字ID（如base64编码）：打开新窗口预览
                        window.open($A.mainUrl('single/file/' + id));
                    }
                    break;

                case 'contact':
                    this.beforeNavigate?.();
                    this.$store.dispatch('openDialogUserid', numId).catch(({ msg }) => {
                        $A.modalError(msg);
                    });
                    break;

                case 'message':
                    this.beforeNavigate?.();
                    this.$store.dispatch('openDialog', numId).then(() => {
                        if (numId2) {
                            this.$store.state.dialogSearchMsgId = numId2;
                        }
                    }).catch(({ msg }) => {
                        $A.modalError(msg);
                    });
                    break;
            }
        },

        /**
         * 处理 AI 回复内联深链
         * 格式: dootask://link/<id>（id 取自深链目录 deep-links.js）
         */
        handleDeepLink(href) {
            const match = href.match(/^dootask:\/\/link\/([a-z_]+)/);
            if (!match || !isDeepLinkId(match[1])) {
                return;
            }
            // 导航策略统一交给 beforeNavigate（移动端关闭、桌面端全屏退全屏、桌面端非全屏保留）
            this.beforeNavigate?.();
            openDeepLink(match[1]);
        },

        /**
         * 处理 AI 建议采纳
         * 格式: dootask://ai-apply/{type}/{task_id}/{msg_id}?{params}
         */
        handleAiApply(href) {
            const match = href.match(/^dootask:\/\/ai-apply\/(\w+)\/(\d+)\/(\d+)(?:\?(.*))?$/);
            if (!match) {
                return;
            }
            const [, type, taskId, msgId, queryString] = match;
            const params = new URLSearchParams(queryString || '');

            // 构建请求数据
            const requestData = {
                task_id: parseInt(taskId, 10),
                msg_id: parseInt(msgId, 10),
                type,
            };

            // assignee 类型传递 userid
            if (type === 'assignee' && params.get('userid')) {
                requestData.userid = parseInt(params.get('userid'), 10);
            }

            // similar 类型传递 related
            if (type === 'similar' && params.get('related')) {
                requestData.related = parseInt(params.get('related'), 10);
            }

            // 调用接口标记为已采纳
            this.$store.dispatch('applyAiSuggestion', requestData).then(({data}) => {
                // 更新本地消息
                if (data.msg) {
                    this.$store.dispatch('saveDialogMsg', data.msg);
                }
                // 根据类型调用对应的业务接口
                this.applyAiSuggestionByType(data.type, data.task_id, data.result, params);
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },

        /**
         * 根据类型执行对应的业务操作
         */
        applyAiSuggestionByType(type, taskId, result, params) {
            switch (type) {
                case 'description':
                    // 更新任务描述（Markdown 转 HTML）
                    this.$store.dispatch('taskUpdate', {
                        task_id: taskId,
                        content: MarkdownConver(result.content),
                    }).then(() => {
                        $A.messageSuccess('应用成功');
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    });
                    break;

                case 'subtasks':
                    // 批量创建子任务
                    this.createSubtasksSequentially(taskId, result.content || []);
                    break;

                case 'assignee':
                    // 增加负责人（保留现有负责人）
                    const userid = params.get('userid');
                    if (!userid || isNaN(parseInt(userid, 10))) {
                        $A.modalError('请选择负责人');
                        return;
                    }
                    const newUserId = parseInt(userid, 10);
                    // 从缓存获取任务当前负责人
                    const task = this.$store.state.cacheTasks.find(t => t.id === taskId);
                    const currentOwners = task?.task_user?.filter(u => u.owner === 1).map(u => u.userid) || [];
                    // 追加新负责人（避免重复）
                    const owners = [...new Set([...currentOwners, newUserId])];
                    this.$store.dispatch('taskUpdate', {
                        task_id: taskId,
                        owner: owners,
                    }).then(() => {
                        $A.messageSuccess('应用成功');
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    });
                    break;

                case 'similar':
                    // 相似任务关联（后端已处理）
                    $A.messageSuccess('应用成功');
                    break;

                default:
                    $A.modalError('未知的建议类型');
            }
        },

        /**
         * 顺序创建子任务
         */
        createSubtasksSequentially(taskId, subtasks) {
            if (!subtasks || subtasks.length === 0) {
                $A.modalError('没有有效的子任务');
                return;
            }

            let completed = 0;
            const total = subtasks.length;

            const createNext = (index) => {
                if (index >= total) {
                    $A.messageSuccess('应用成功');
                    return;
                }
                const name = subtasks[index];
                if (!name || typeof name !== 'string' || !name.trim()) {
                    createNext(index + 1);
                    return;
                }
                this.$store.dispatch('taskAddSub', {
                    task_id: taskId,
                    name: name.trim(),
                }).then(() => {
                    completed++;
                    createNext(index + 1);
                }).catch(({msg}) => {
                    // 单个失败不影响后续创建
                    console.warn(`创建子任务失败: ${name}`, msg);
                    createNext(index + 1);
                });
            };

            createNext(0);
        },

        /**
         * 处理 AI 建议忽略
         * 格式: dootask://ai-dismiss/{type}/{task_id}/{msg_id}?userid=xxx&related=xxx
         */
        handleAiDismiss(href) {
            const match = href.match(/^dootask:\/\/ai-dismiss\/(\w+)\/(\d+)\/(\d+)(\?.*)?$/);
            if (!match) {
                return;
            }
            const [, type, taskId, msgId, queryString] = match;
            const params = new URLSearchParams(queryString || '');

            const data = {
                task_id: parseInt(taskId, 10),
                msg_id: parseInt(msgId, 10),
                type,
            };

            // assignee 类型传递 userid 用于单独忽略
            if (type === 'assignee' && params.get('userid')) {
                data.userid = parseInt(params.get('userid'), 10);
            }

            // similar 类型传递 related 用于单独忽略
            if (type === 'similar' && params.get('related')) {
                data.related = parseInt(params.get('related'), 10);
            }

            this.$store.dispatch('dismissAiSuggestion', data).then(({data: respData}) => {
                // 更新本地消息
                if (respData.msg) {
                    this.$store.dispatch('saveDialogMsg', respData.msg);
                }
                $A.messageSuccess(this.$L('已忽略'));
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        }
    }
}
</script>
