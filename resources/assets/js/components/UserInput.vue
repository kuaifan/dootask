<template>
    <div :class="['common-user', maxHiddenClass]">
        <Select
            ref="select"
            v-model="selects"
            :transfer="transfer"
            :placeholder="placeholder"
            :size="size"
            :loading="loadIng > 0"
            :loading-text="$L('加载中...')"
            :default-label="value"
            :default-event-object="true"
            :multiple-max="multipleMax"
            :multiple-uncancelable="uncancelable"
            :remote-method="remoteMethod"
            @on-query-change="searchUser"
            @on-open-change="openChange"
            multiple
            filterable
            transfer-class-name="common-user-transfer">
            <div v-if="multipleMax" slot="drop-prepend" class="user-drop-prepend">{{$L('最多只能选择' + multipleMax + '个')}}</div>
            <slot name="option-prepend"></slot>
            <Option
                v-for="(item, key) in list"
                :value="item.userid"
                :key="key"
                :key-value="item.email"
                :label="item.nickname"
                :avatar="item.userimg"
                :disabled="isDisabled(item.userid)">
                <div class="user-input-option">
                    <div class="user-input-avatar"><EAvatar class="avatar" :src="item.userimg"/></div>
                    <div class="user-input-nickname">{{ item.nickname }}</div>
                    <div class="user-input-userid">ID: {{ item.userid }}</div>
                </div>
            </Option>
        </Select>
        <div v-if="loadIng > 0" class="common-user-loading"><Loading/></div>
    </div>
</template>

<script>
    import {Store} from 'le5le-store';

    export default {
        name: 'UserInput',
        props: {
            value: {
                type: [String, Number, Array],
                default: ''
            },
            uncancelable: {
                type: Array,
                default: () => {
                    return [];
                }
            },
            disabledChoice: {
                type: Array,
                default: () => {
                    return [];
                }
            },
            placeholder: {
                default: ''
            },
            size: {
                default: 'default'
            },
            transfer: {
                type: Boolean,
                default: true
            },
            multipleMax: {
                type: Number,
            },
            maxHiddenInput: {
                type: Boolean,
                default: true
            },
            projectId: {
                type: Number,
                default: 0
            },
            noProjectId: {
                type: Number,
                default: 0
            },
        },
        data() {
            return {
                loadIng: 0,

                selects: [],
                list: [],

                searchKey: null,
                searchHistory: [],

                subscribe: null,
            }
        },
        mounted() {
            this.subscribe = Store.subscribe('cacheUserActive', (data) => {
                let index = this.list.findIndex(({userid}) => userid == data.userid);
                if (index > -1) {
                    this.$set(this.list, index, Object.assign({}, this.list[index], data));
                    this.handleSelectData();
                }
            });
        },
        beforeDestroy() {
            if (this.subscribe) {
                this.subscribe.unsubscribe();
                this.subscribe = null;
            }
        },
        computed: {
            maxHiddenClass() {
                const {multipleMax, maxHiddenInput, selects} = this;
                if (multipleMax && maxHiddenInput) {
                    if (selects.length >= multipleMax) {
                        return 'hidden-input'
                    }
                }
                return '';
            }
        },
        watch: {
            value: {
                handler() {
                    const tmpId = this._tmpId = $A.randomString(6)
                    setTimeout(() => {
                        if (tmpId === this._tmpId) this.valueChange()
                    }, 10)
                },
                immediate: true,
            },
            selects(val) {
                this.$emit('input', val);
            }
        },
        methods: {
            searchUser(key) {
                if (typeof key !== "string") key = "";
                this.searchKey = key;
                //
                const history = this.searchHistory.find(item => item.key == key);
                if (history) this.list = history.data;
                //
                if (!history) this.loadIng++;
                setTimeout(() => {
                    if (this.searchKey != key) {
                        if (!history) this.loadIng--;
                        return;
                    }
                    this.$store.dispatch("call", {
                        url: 'users/search',
                        data: {
                            keys: {
                                key,
                                project_id: this.projectId,
                                no_project_id: this.noProjectId,
                            },
                            take: 30
                        },
                    }).then(({data}) => {
                        if (!history) this.loadIng--;
                        this.list = data;
                        //
                        const index = this.searchHistory.findIndex(item => item.key == key);
                        const tmpData = {
                            key,
                            data,
                            time: $A.Time()
                        };
                        if (index > -1) {
                            this.searchHistory.splice(index, 1, tmpData)
                        } else {
                            this.searchHistory.push(tmpData)
                        }
                    }).catch(({msg}) => {
                        if (!history) this.loadIng--;
                        this.list = [];
                        $A.messageWarning(msg);
                    });
                }, this.searchHistory.length > 0 ? 300 : 0)
            },

            isDisabled(userid) {
                if (this.disabledChoice.length === 0) {
                    return false;
                }
                return this.disabledChoice.includes(userid)
            },

            openChange(show) {
                if (show) {
                    this.$nextTick(this.searchUser);
                }
            },

            remoteMethod() {
                //
            },

            valueChange() {
                if (this.selects == this.value) {
                    return
                }
                if ($A.isArray(this.value)) {
                    this.selects = $A.cloneJSON(this.value);
                } else if (this.value) {
                    this.selects = [this.value]
                } else {
                    this.selects = [];
                }
                this.selects.some(userid => {
                    if (!this.list.find(item => item.userid == userid)) {
                        this.list.push({userid, nickname: userid});
                        this.$store.dispatch("getUserBasic", {userid});
                    }
                })
            },

            handleSelectData() {
                this.__handleSelectTimeout && clearTimeout(this.__handleSelectTimeout);
                this.__handleSelectTimeout = setTimeout(() => {
                    if (!this.$refs.select) {
                        return;
                    }
                    const list = this.$refs.select.getValue();
                    list && list.some(option => {
                        const data = this.list.find(({userid}) => userid == option.value)
                        if (data) {
                            this.$set(option, 'label', data.nickname)
                            this.$set(option, 'avatar', data.userimg)
                        }
                    })
                }, 100);
            }
        }
    };
</script>
