<template>
    <div v-if="repoStatus && !$store.state.windowMax768" class="common-app-down">
        <div v-if="isElectron" class="common-app-down-link" @click="openExternal(repoData.html_url)">
            <Icon type="md-download"/> {{$L(repoTitle)}}
        </div>
        <a v-else class="common-app-down-link" :href="repoData.html_url" target="_blank">
            <Icon type="md-download"/> {{$L(repoTitle)}}
        </a>
    </div>
</template>

<script>
import Vue from 'vue'
import MarkdownPreview from "./MDEditor/components/preview";
Vue.component('MarkdownPreview', MarkdownPreview)

import axios from "axios";
import { Notification } from 'element-ui';

export default {
    name: 'AppDown',
    data() {
        return {
            repoName: 'kuaifan/dootask',
            repoData: {},
            repoStatus: 0, // 0 没有，1有客户端，2客户端有新版本
        }
    },
    mounted() {
        this.getReleases();
    },
    computed: {
        repoTitle() {
            return this.repoStatus == 2 ? '更新客户端' : '客户端下载';
        }
    },
    watch: {
        repoData: {
            handler(data) {
                if (!data.tag_name) {
                    this.repoStatus = 0;
                    return;
                }
                if (!this.isElectron) {
                    // 网页只提示有客户端下载
                    this.repoStatus = 1;
                    return;
                }
                // 客户端提示更新
                let currentVersion = window.systemInformation.version;
                let latestVersion = $A.leftDelete(data.tag_name.toLowerCase(), "v")
                if (this.compareVersion(latestVersion, currentVersion) === 1) {
                    // 有新版本
                    const h = this.$createElement;
                    window.__appNotification && window.__appNotification.close();
                    window.__appNotification = Notification({
                        title: this.$L("更新提示"),
                        duration: 0,
                        position: "bottom-right",
                        customClass: "common-app-down-notification",
                        onClose: () => {
                            this.repoStatus = 2;
                        },
                        message: h('span', [
                            h('span', [
                                h('span', this.$L('发现新版本') + ": "),
                                h('Tag', {
                                    props: {
                                        color: 'volcano'
                                    }
                                }, data.tag_name)
                            ]),
                            h('MarkdownPreview', {
                                class: 'common-app-down-body',
                                props: {
                                    initialValue: data.body
                                }
                            }),
                            h('div', {
                                class: 'common-app-down-link',
                                on: {
                                    click: () => {
                                        this.openExternal(data.html_url);
                                    }
                                },
                            }, [
                                h('Icon', {
                                    props: {
                                        type: 'md-download'
                                    },
                                    style: {
                                        marginRight: '5px'
                                    }
                                }),
                                h('span', this.$L('立即升级'))
                            ]),
                        ])
                    });
                }
            },
            deep: true
        }
    },
    methods: {
        getReleases() {
            let appdown = this.$store.state.method.getStorageJson("cacheAppdown");
            if (appdown.time && appdown.time + 3600 > Math.round(new Date().getTime() / 1000)) {
                this.chackReleases(appdown.data)
                return;
            }
            ;(() => {
                axios
                    .get("https://api.github.com/repos/" + this.repoName + "/releases/latest")
                    .then(({status, data}) => {
                        if (status === 200) {
                            this.$store.state.method.setStorage("cacheAppdown", {
                                time: Math.round(new Date().getTime() / 1000),
                                data: data
                            });
                            this.chackReleases(data)
                        }
                    });
            })();
        },

        chackReleases(data) {
            let hostname = window.location.hostname;
            if (hostname == '127.0.0.1') {
                hostname = "www.dootask.com"
            }
            let assets = data.assets || [];
            let asset = assets.find(({browser_download_url}) => {
                return $A.strExists(browser_download_url, hostname)
            });
            if (asset) {
                this.repoData = data;
            }
        },

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

        openExternal(url) {
            try {
                this.$electron.shell.openExternal(url);
            } catch (e) {
                window.location.href = url;
            }
        }
    }
};
</script>
