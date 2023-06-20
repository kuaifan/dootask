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
            <div v-if="multipleMax" slot="drop-prepend" class="user-drop-prepend">
                <div class="user-drop-text">
                    {{$L('最多只能选择' + multipleMax + '个')}}
                    <em v-if="selects.length">({{$L(`已选${selects.length}个`)}})</em>
                </div>
                <Checkbox class="user-drop-check" v-model="multipleCheck" @on-change="onMultipleChange"></Checkbox>
            </div>
            <slot name="option-prepend"></slot>
            <Option
                v-for="(item, key) in list"
                :value="item.userid"
                :key="key"
                :key-value="`${item.email}|${item.pinyin}`"
                :label="item.nickname"
                :avatar="item.userimg"
                :disabled="isDisabled(item.userid)">
                <div class="user-input-option">
                    <div class="user-input-avatar"><EAvatar class="avatar" :src="item.userimg"/></div>
                    <div v-if="item.bot" class="taskfont user-input-bot">&#xe68c;</div>
                    <div v-if="item.disable_at" class="user-input-disable">[{{$L('离职')}}]</div>
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
            maxHiddenSelect: {
                type: Boolean,
                default: false
            },
            projectId: {
                type: Number,
                default: 0
            },
            noProjectId: {
                type: Number,
                default: 0
            },
            dialogId: {
                type: Number,
                default: 0
            },
            showBot: {
                type: Boolean,
                default: false
            },
            showDisable: {
                type: Boolean,
                default: false
            },
        },
        data() {
            return {
                loadIng: 0,

                selects: [],
                list: [],

                multipleCheck: false,

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
                this.$emit('input', val)
                if (this.maxHiddenSelect && val.length >= this.maxHiddenSelect && this.$refs.select) {
                    this.$refs.select.hideMenu()
                }
                this.calcMultipleSelect()
            }
        },
        methods: {
            searchUser(key) {
                if (typeof key !== "string") key = "";
                this.searchKey = key;
                //
                const history = this.searchHistory.find(item => item.key == key);
                if (history) {
                    this.list = history.data
                    this.calcMultipleSelect()
                }
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
                                dialog_id: this.dialogId,
                                bot: this.showBot && key ? 2 : 0,
                                disable: this.showDisable && key ? 2 : 0,
                            },
                            take: 50
                        },
                    }).then(({data}) => {
                        this.list = data
                        this.calcMultipleSelect()
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
                        this.list = []
                        this.calcMultipleSelect()
                        $A.messageWarning(msg)
                    }).finally(_ => {
                        if (!history) this.loadIng--;
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
                this.calcMultipleSelect()
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
                    if (!this.list.find(item => item.userid == userid) && userid) {
                        this.list.push({userid, nickname: userid})
                        this.calcMultipleSelect()
                        this.$store.dispatch("getUserBasic", {userid})
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
            },

            calcMultipleSelect() {
                if (this.multipleMax && this.list.length > 0) {
                    this.calcMultipleTime && clearTimeout(this.calcMultipleTime)
                    this.calcMultipleTime = setTimeout(_ => {
                        let allSelected = true
                        this.$refs.select.selectOptions.some(({componentInstance}) => {
                            if (!this.selects.includes(componentInstance.value)) {
                                allSelected = false
                            }
                        })
                        this.multipleCheck = allSelected
                    }, 10)
                } else {
                    this.multipleCheck = false
                }
            },

            onMultipleChange(val) {
                if (val) {
                    let optional = this.multipleMax - this.selects.length
                    this.$refs.select.selectOptions.some(({componentInstance}) => {
                        if (this.multipleMax && optional <= 0) {
                            this.$nextTick(_ => {
                                $A.messageWarning("已超过最大选择数量")
                                this.multipleCheck = false
                            })
                            return true
                        }
                        if (!this.selects.includes(componentInstance.value)) {
                            componentInstance.select()
                            optional--
                        }
                    })
                } else {
                    this.selects = []
                }
            }
        }
    };
</script>
