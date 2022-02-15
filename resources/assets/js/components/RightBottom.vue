<template>
    <div class="common-right-bottom">
        <div v-if="showSSO" class="common-right-bottom-link" @click="useSSOLogin">
            <Icon type="ios-globe-outline"/>
            {{ $L('使用 SSO 登录') }}
        </div>
        <template v-if="showDown">
            <div v-if="$Electron" class="common-right-bottom-link" @click="releasesNotification">
                <Icon type="md-download"/>
                {{ $L(repoTitle) }}
            </div>
            <a v-else class="common-right-bottom-link" :href="repoReleases.html_url" target="_blank">
                <Icon type="md-download"/>
                {{ $L(repoTitle) }}
            </a>
        </template>
    </div>
</template>

<script>
import Vue from 'vue'
import MarkdownPreview from "./MDEditor/components/preview";
import axios from "axios";
Vue.component('MarkdownPreview', MarkdownPreview)

import {mapState} from "vuex";
import {Store} from "le5le-store";

export default {
    name: 'RightBottom',
    data() {
        return {
            loadIng: 0,

            repoName: 'kuaifan/dootask',
            repoData: {},
            repoStatus: 0,  // 0 没有，1有客户端，2客户端有新版本
            repoReleases: {},

            downloadResult: {},
        }
    },
    mounted() {
        this.getReleases();
        //
        if (this.$Electron) {
            this.$Electron.ipcRenderer.on('downloadDone', (event, {result}) => {
                if (result.name == this.repoData.name) {
                    this.downloadResult = result;
                    this.releasesNotification()
                }
            })
        }
    },
    computed: {
        ...mapState([
            'isDesktop',
            'wsOpenNum',
        ]),

        repoTitle() {
            return this.repoStatus == 2 ? '更新客户端' : '客户端下载';
        },

        showSSO() {
            return this.$Electron && ['login'].includes(this.$route.name)
        },

        showDown() {
            return this.repoStatus && this.isDesktop && ['login', 'manage-dashboard'].includes(this.$route.name)
        }
    },
    watch: {
        wsOpenNum(num) {
            if (num <= 1) return
            this.wsOpenTimeout && clearTimeout(this.wsOpenTimeout)
            this.wsOpenTimeout = setTimeout(this.getReleases, 5000)
        },
    },
    methods: {
        compareVersion(version1, version2) {
            let pA = 0, pB = 0;
            // 寻找当前区间的版本号
            const findDigit = (str, start) => {
                let i = start;
                while (str[i] !== '.' && i < str.length) {
                    i++;
                }
                return i;
            }

            while (pA < version1.length && pB < version2.length) {
                const nextA = findDigit(version1, pA);
                const nextB = findDigit(version2, pB);
                const numA = +version1.substr(pA, nextA - pA);
                const numB = +version2.substr(pB, nextB - pB);
                if (numA !== numB) {
                    return numA > numB ? 1 : -1;
                }
                pA = nextA + 1;
                pB = nextB + 1;
            }
            // 若arrayA仍有小版本号
            while (pA < version1.length) {
                const nextA = findDigit(version1, pA);
                const numA = +version1.substr(pA, nextA - pA);
                if (numA > 0) {
                    return 1;
                }
                pA = nextA + 1;
            }
            // 若arrayB仍有小版本号
            while (pB < version2.length) {
                const nextB = findDigit(version2, pB);
                const numB = +version2.substr(pB, nextB - pB);
                if (numB > 0) {
                    return -1;
                }
                pB = nextB + 1;
            }
            // 版本号完全相同
            return 0;
        },

        getReleases() {
            if (this.repoStatus > 0) {
                return;
            }
            if (this.loadIng > 0) {
                return;
            }
            //
            let cache = $A.getStorageJson("cacheAppdown");
            let timeout = 600;
            if (cache.time && cache.time + timeout > Math.round(new Date().getTime() / 1000)) {
                this.repoReleases = cache.data;
                this.chackReleases()
                setTimeout(this.getReleases, timeout * 1000)
                return;
            }
            //
            this.loadIng++;
            axios.get("https://api.github.com/repos/" + this.repoName + "/releases/latest").then(({status, data}) => {
                this.loadIng--;
                if (status === 200) {
                    cache = {
                        time: Math.round(new Date().getTime() / 1000),
                        data: data
                    }
                    $A.setStorage("cacheAppdown", cache);
                    this.repoReleases = cache.data;
                    this.chackReleases()
                }
                setTimeout(this.getReleases, timeout * 1000)
            }).catch(() => {
                this.loadIng--;
                setTimeout(this.getReleases, timeout * 1000)
            });
        },

        chackReleases() {
            let hostName = $A.getDomain(window.systemInfo.apiUrl);
            if (hostName == "" || $A.leftExists(hostName, '127.0.0.1')) {
                hostName = "public"
            }
            if (this.$Electron) {
                // 客户端（更新）
                let match = (window.navigator.userAgent + "").match(/\s+(Main|Sub)TaskWindow\/(.*?)\/(.*?)\//)
                if (!match) {
                    return;
                }
                let artifactName = null;
                if (match[2] === 'darwin') {
                    artifactName = `${hostName}-${this.repoReleases.tag_name}-mac-${match[3]}.pkg`;
                } else if (match[2] === 'win32') {
                    artifactName = `${hostName}-${this.repoReleases.tag_name}-win-${match[3]}.exe`;
                } else {
                    return;
                }
                this.repoData = (this.repoReleases.assets || []).find(({name}) => name == artifactName);
                if (!this.repoData) {
                    return;
                }
                let currentVersion = window.systemInfo.version;
                let latestVersion = $A.leftDelete(this.repoReleases.tag_name.toLowerCase(), "v")
                if (this.compareVersion(latestVersion, currentVersion) === 1) {
                    // 有新版本
                    console.log("New version: " + latestVersion);
                    this.$Electron.ipcRenderer.send('downloadFile', {
                        url: this.repoData.browser_download_url
                    });
                }
            } else {
                // 网页版（提示有客户端下载）
                this.repoData = (this.repoReleases.assets || []).find(({name}) => $A.strExists(name, hostName));
                if (this.repoData) {
                    let latestVersion = $A.leftDelete(this.repoReleases.tag_name.toLowerCase(), "v")
                    console.log("Exist client: " + latestVersion);
                    this.repoStatus = 1;
                }
            }
        },

        releasesNotification() {
            $A.modalConfirm({
                okText: this.$L('立即更新'),
                onOk: () => {
                    this.installApplication();
                },
                onCancel: () => {
                    this.repoStatus = 2;
                },
                render: (h) => {
                    return h('div', {
                        class: 'common-right-bottom-notification'
                    }, [
                        h('div', {
                            class: "notification-head"
                        }, [
                            h('div', {
                                class: "notification-title"
                            }, this.$L('发现新版本')),
                            h('Tag', {
                                props: {
                                    color: 'volcano'
                                }
                            }, this.repoReleases.tag_name)
                        ]),
                        h('MarkdownPreview', {
                            class: 'notification-body',
                            props: {
                                initialValue: this.repoReleases.body
                            }
                        }),
                    ])
                }
            });
        },

        installApplication() {
            if (!this.$Electron) {
                return;
            }
            this.$Electron.ipcRenderer.send('openFile', {
                path: this.downloadResult.savePath
            });
            this.$Electron.ipcRenderer.send('windowQuit');
        },

        useSSOLogin() {
            Store.set('useSSOLogin', true);
        }
    }
};
</script>
