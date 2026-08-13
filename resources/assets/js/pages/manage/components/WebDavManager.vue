<template>
    <Modal
        v-model="visible"
        :title="$L('WebDAV')"
        :mask-closable="false"
        width="640">
        <div class="webdav-manager">
            <Spin v-if="loading > 0" fix/>

            <Alert v-if="status && (!status.enabled || !status.allowed)" type="warning" show-icon>
                {{status.enabled ? $L('你没有 WebDAV 使用权限') : $L('WebDAV 未启用')}}
            </Alert>

            <template v-else-if="status">
                <Alert v-if="!status.https" type="warning" show-icon>
                    {{$L('当前页面不是 HTTPS，无法创建应用密码')}}
                </Alert>
                <div class="webdav-section">
                    <div class="webdav-section-title">{{$L('连接地址')}}</div>
                    <div class="webdav-copy-row">
                        <Input :value="status.url" readonly/>
                        <Button icon="ios-copy-outline" @click="copyText(status.url)">{{$L('复制')}}</Button>
                    </div>
                </div>

                <Alert v-if="created.password" type="success" show-icon class="webdav-secret">
                    {{$L('应用密码已创建，请立即保存，关闭后无法再次查看。')}}
                    <div slot="desc" class="webdav-secret-fields">
                        <div>
                            <span>{{$L('用户名')}}</span>
                            <div class="webdav-copy-row">
                                <Input :value="created.public_id" readonly/>
                                <Button icon="ios-copy-outline" @click="copyText(created.public_id)">{{$L('复制')}}</Button>
                            </div>
                        </div>
                        <div>
                            <span>{{$L('应用密码')}}</span>
                            <div class="webdav-copy-row">
                                <Input :value="created.password" readonly/>
                                <Button icon="ios-copy-outline" @click="copyText(created.password)">{{$L('复制')}}</Button>
                            </div>
                        </div>
                    </div>
                </Alert>

                <div class="webdav-section">
                    <div class="webdav-section-head">
                        <div class="webdav-section-title">{{$L('应用密码')}}</div>
                        <Button
                            v-if="!createVisible"
                            type="primary"
                            icon="md-add"
                            :disabled="!status.https || credentials.length >= status.max_credentials"
                            @click="createVisible=true">
                            {{$L('新建')}}
                        </Button>
                    </div>

                    <Form v-if="createVisible" class="webdav-create" @submit.native.prevent>
                        <FormItem :label="$L('设备名称')">
                            <Input v-model="createForm.name" :maxlength="100" :placeholder="$L('例如：办公室电脑')"/>
                        </FormItem>
                        <FormItem :label="$L('有效期')">
                            <InputNumber
                                v-model="createForm.expire_days"
                                :min="1"
                                :max="status.max_expire_days"/>
                            <span class="webdav-days">{{$L('[day_unit].天')}}</span>
                        </FormItem>
                        <div class="webdav-create-actions">
                            <Button @click="createVisible=false">{{$L('取消')}}</Button>
                            <Button type="primary" :loading="creating" @click="createCredential">{{$L('创建')}}</Button>
                        </div>
                    </Form>

                    <div v-if="credentials.length" class="webdav-credentials">
                        <div v-for="item in credentials" :key="item.id" class="webdav-credential">
                            <div class="webdav-credential-main">
                                <strong>{{item.name}}</strong>
                                <span>{{item.public_id}} · ****{{item.password_suffix}}</span>
                                <span>
                                    {{$L('有效期至')}}：{{item.expires_at || $L('永久')}}
                                    <template v-if="item.last_used_at"> · {{$L('最近使用')}}：{{item.last_used_at}}</template>
                                </span>
                            </div>
                            <Tag :color="item.status === 'active' ? 'green' : 'default'">{{statusText(item.status)}}</Tag>
                            <Button
                                v-if="item.status === 'active'"
                                type="text"
                                icon="ios-trash-outline"
                                class="webdav-revoke"
                                @click="revokeCredential(item)"/>
                        </div>
                    </div>
                    <div v-else-if="!createVisible" class="webdav-empty">{{$L('暂无应用密码')}}</div>
                </div>
            </template>
        </div>
        <div slot="footer">
            <Button @click="visible=false">{{$L('关闭')}}</Button>
        </div>
    </Modal>
</template>

<script>
export default {
    name: 'WebDavManager',
    props: {
        value: Boolean,
    },
    data() {
        return {
            visible: false,
            loading: 0,
            creating: false,
            status: null,
            credentials: [],
            created: {},
            createVisible: false,
            createForm: {
                name: '',
                expire_days: 90,
            },
        }
    },
    watch: {
        value: {
            immediate: true,
            handler(value) {
                this.visible = value;
                if (value) this.load();
            },
        },
        visible(value) {
            this.$emit('input', value);
            if (!value) {
                this.created = {};
                this.createVisible = false;
            }
        },
    },
    methods: {
        load() {
            this.loading++;
            Promise.all([
                this.$store.dispatch('call', {url: 'file/dav/status'}),
                this.$store.dispatch('call', {url: 'file/dav/credentials'}),
            ]).then(([status, credentials]) => {
                this.status = status.data;
                this.credentials = credentials.data || [];
                this.createForm.expire_days = this.status.default_expire_days;
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(() => {
                this.loading--;
            });
        },
        createCredential() {
            if (!this.createForm.name.trim()) {
                $A.messageWarning('请输入设备名称');
                return;
            }
            this.creating = true;
            this.$store.dispatch('call', {
                url: 'file/dav/create',
                method: 'post',
                data: this.createForm,
            }).then(({data}) => {
                this.created = data;
                this.createVisible = false;
                this.createForm.name = '';
                return this.$store.dispatch('call', {url: 'file/dav/credentials'});
            }).then(({data}) => {
                this.credentials = data || [];
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(() => {
                this.creating = false;
            });
        },
        revokeCredential(item) {
            $A.modalConfirm({
                title: '撤销应用密码',
                content: '撤销后，使用此应用密码连接的设备将立即断开。',
                onOk: () => this.$store.dispatch('call', {
                    url: 'file/dav/revoke',
                    method: 'post',
                    data: {id: item.id},
                }).then(() => this.load()),
            });
        },
        statusText(status) {
            if (status === 'active') return $L('[credential_status].有效');
            if (status === 'expired') return $L('已过期');
            if (status === 'revoked') return $L('已撤销');
            return status;
        },
    },
}
</script>

<style lang="scss" scoped>
.webdav-manager {
    min-height: 180px;
    position: relative;
}
.webdav-section + .webdav-section,
.webdav-secret {
    margin-top: 20px;
}
.webdav-section-title {
    color: #17233d;
    font-size: 15px;
    font-weight: 600;
}
.webdav-section-head,
.webdav-copy-row,
.webdav-credential {
    display: flex;
    align-items: center;
}
.webdav-section-head {
    justify-content: space-between;
    margin-bottom: 12px;
}
.webdav-copy-row {
    gap: 8px;
    margin-top: 8px;
}
.webdav-copy-row .ivu-input-wrapper {
    min-width: 0;
}
.webdav-secret-fields > div {
    margin-top: 10px;
}
.webdav-create {
    padding: 14px 0;
    border-top: 1px solid #f0f0f0;
    border-bottom: 1px solid #f0f0f0;
}
.webdav-days {
    margin-left: 8px;
}
.webdav-create-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
.webdav-credential {
    min-height: 70px;
    gap: 12px;
    border-bottom: 1px solid #f0f0f0;
}
.webdav-credential-main {
    flex: 1;
    min-width: 0;
}
.webdav-credential-main strong,
.webdav-credential-main span {
    display: block;
    overflow-wrap: anywhere;
}
.webdav-credential-main span {
    color: #808695;
    font-size: 12px;
    margin-top: 3px;
}
.webdav-revoke {
    color: #ed4014;
}
.webdav-empty {
    color: #808695;
    padding: 24px 0;
    text-align: center;
}
</style>
