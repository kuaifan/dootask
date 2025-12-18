<template>
    <MicroApps ref="app" window-type="popout"/>
</template>

<script>
import {mapState} from "vuex";
import MicroApps from "../../components/MicroApps";

export default {
    components: { MicroApps },
    computed: {
        ...mapState(['userIsAdmin']),
    },

    async mounted() {
        const {name} = this.$route.params;
        if (!name) {
            $A.modalError("应用不存在");
            return
        }

        // iframe 测试
        if (name === 'iframe-test') {
            if (!this.userIsAdmin) {
                $A.modalError("仅管理员可使用此功能")
                return
            }
            let {url} = this.$route.query;
            if (!url) {
                url = await this.promptIframeUrl();
                if (!url) {
                    return
                }
                this.$router.replace({
                    path: this.$route.path,
                    query: {
                        ...this.$route.query,
                        url
                    }
                }).catch(() => {});
            }
            await this.$refs.app.onOpen({
                id: 'iframe-test',
                name: 'iframe-test',
                url: url,
                type: 'iframe',
                transparent: true,
                keep_alive: false,
            })
            return
        }

        const app = (await $A.IDBArray("cacheMicroApps")).reverse().find(item => item.name === name);
        if (!app) {
            $A.modalError("应用不存在");
            return
        }

        await this.$refs.app.onOpen(app)
    },
    methods: {
        promptIframeUrl() {
            return new Promise((resolve, reject) => {
                $A.modalInput({
                    title: this.$L("请输入 URL"),
                    placeholder: "https://example.com",
                    onOk: (val) => {
                        const input = (val || "").trim();
                        if (!input) {
                            return this.$L("URL不能为空");
                        }
                        resolve(input);
                    },
                    onCancel: () => reject()
                });
            }).catch(() => null);
        }
    }
}
</script>
