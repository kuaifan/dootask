<template>
    <div v-if="ready" :class="['common-user', maxHiddenClass]">
        <Select
            v-model="values"
            :transfer="transfer"
            :remote-method="searchUser"
            :placeholder="placeholder"
            :loading="loading"
            :loading-text="$L('加载中...')"
            :default-label="value"
            :default-event-object="true"
            :multipleMax="multipleMax"
            :multipleUncancelable="uncancelable"
            multiple
            filterable
            transfer-class-name="common-user-transfer"
            @on-open-change="openChange"
            @on-set-default-options="setDefaultOptions">
            <div v-if="multipleMax" slot="drop-prepend" class="user-drop-prepend">{{$L('最多只能选择' + multipleMax + '个')}}</div>
            <Option v-for="(item, key) in lists" :value="item.userid" :key="key" :label="item.nickname" :avatar="item.userimg">
                <div class="user-input-option">
                    <div class="user-input-avatar"><Avatar :src="item.userimg"/></div>
                    <div class="user-input-nickname">{{ item.nickname }}</div>
                    <div class="user-input-userid">ID: {{ item.userid }}</div>
                </div>
            </Option>
        </Select>
        <div v-if="!initialized" class="common-user-loading"><Loading/></div>
    </div>
</template>

<script>
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
            placeholder: {
                default: ''
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
        },
        data() {
            return {
                ready: false,
                initialized: false,
                loading: false,
                openLoad: false,
                values: [],
                lists: []
            }
        },
        mounted() {
            if ($A.isArray(this.value)) {
                this.values = $A.cloneJSON(this.value);
            } else {
                this.$emit('input', this.value ? [this.value] : []);
            }
            this.$nextTick(() => {
                this.ready = true;
            });
        },
        computed: {
            maxHiddenClass() {
                const {multipleMax, maxHiddenInput, values} = this;
                if (multipleMax && maxHiddenInput) {
                    if (values.length >= multipleMax) {
                        return 'hidden-input'
                    }
                }
                return '';
            }
        },
        watch: {
            value(val) {
                this.values = val;
            },
            values(val) {
                this.$emit('input', val);
            }
        },
        methods: {
            openChange(show) {
                if (show && !this.openLoad) {
                    this.openLoad = true;
                    if (this.lists.length == this.values.length) {
                        this.$nextTick(this.searchUser);
                    }
                }
            },

            setDefaultOptions(options) {
                const userids = [];
                options.forEach(({value, label}) => {
                    this.lists.push({
                        userid: value,
                        nickname: label,
                    });
                    userids.push(value);
                });
                //
                this.$store.commit('getUserBasic', {
                    userid: userids,
                    complete: () => {
                        this.initialized = true;
                    },
                    success: (user) => {
                        let option = options.find(({value}) => value == user.userid);
                        if (option) {
                            this.$set(option, 'label', user.nickname)
                            this.$set(option, 'avatar', user.userimg)
                        }
                        this.lists.some((item, index) => {
                            if (item.userid == user.userid) {
                                this.$set(this.lists, index, Object.assign(item, user));
                            }
                        });
                    }
                });
            },

            searchUser(query) {
                if (query !== '') {
                    this.loading = true;
                    $A.apiAjax({
                        url: 'users/search',
                        data: {
                            keys: {
                                key: query || ''
                            },
                            take: 30
                        },
                        complete: () => {
                            this.loading = false;
                        },
                        success: ({ret, data, msg}) => {
                            if (ret === 1) {
                                this.lists = data;
                            } else {
                                this.lists = [];
                                $A.messageWarning(msg);
                            }
                        }
                    });
                } else {
                    this.lists = [];
                }
            }
        }
    };
</script>
