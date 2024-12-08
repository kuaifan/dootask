<template>
    <div class="task-tag-select" :class="{'no-search': filteredTags.length <= 5}">
        <!-- Search Box -->
        <div class="search-box">
            <input
                type="text"
                v-model="searchQuery"
                :placeholder="$L('搜索标签')"
                class="search-input"
            >
        </div>

        <!-- Tag List -->
        <div class="tag-list">
            <template v-if="filteredTags.length">
                <div
                    v-for="tag in filteredTags"
                    :key="tag.name"
                    class="tag-item"
                    :class="{ 'is-selected': isSelected(tag) }"
                    @click="toggleTag(tag)"
                >
                    <div class="tag-color" :style="{ backgroundColor: tag.color }"></div>
                    <div class="tag-info">
                        <div class="tag-name">{{ tag.name }}</div>
                        <div class="tag-desc" v-if="tag.desc">{{ tag.desc }}</div>
                    </div>
                    <div class="tag-check" v-if="isSelected(tag)">
                        <i class="el-icon-check"></i>
                    </div>
                </div>
            </template>
            <div v-else-if="!loading" class="no-data">{{ $L('暂无标签') }}</div>
        </div>

        <!-- Add Button -->
        <div class="footer-box">
            <div class="add-button" @click="$emit('add')">
                <i class="el-icon-plus"></i>
                <span>{{ $L('添加标签') }}</span>
            </div>
        </div>

        <!-- Loading -->
        <Spin v-if="loading" fix/>
    </div>
</template>

<script>
export default {
    name: "TaskTagSelect",
    props: {
        value: {
            type: Array,
            default: () => []
        },
        dataSources: {
            type: Array,
            default: () => []
        },
        loading: {
            type: Boolean,
            default: false
        },
        max: {
            type: Number,
            default: 0
        }
    },
    data() {
        return {
            searchQuery: '',
            internalDataSources: []
        }
    },
    watch: {
        value: {
            immediate: true,
            handler() {
                this.syncValueToDataSources();
            }
        },
        dataSources: {
            immediate: true,
            handler(newVal) {
                this.internalDataSources = [...newVal];
                this.syncValueToDataSources();
            }
        }
    },
    computed: {
        filteredTags() {
            return this.internalDataSources.filter(tag =>
                tag.name.toLowerCase().includes(this.searchQuery.toLowerCase())
            );
        }
    },
    methods: {
        isSelected(tag) {
            return this.value.some(item => item.name === tag.name);
        },
        toggleTag(tag) {
            const isSelected = this.isSelected(tag);
            let newValue;
            if (isSelected) {
                newValue = this.value.filter(item => item.name !== tag.name);
            } else {
                if (this.max > 0 && this.value.length >= this.max) {
                    $A.messageWarning(this.$L('最多只能选择 (*) 个标签', this.max));
                    return;
                }
                newValue = [...this.value, { name: tag.name, color: tag.color }];
            }
            this.$emit('input', newValue);
        },
        syncValueToDataSources() {
            if (!this.value || !this.internalDataSources) return;

            const missingTags = this.value.filter(valueTag =>
                !this.internalDataSources.some(sourceTag => sourceTag.name === valueTag.name)
            );

            if (missingTags.length) {
                this.internalDataSources = [
                    ...missingTags.map(tag => ({
                        name: tag.name,
                        color: tag.color,
                        desc: ''
                    })),
                    ...this.internalDataSources
                ];
            }
        }
    }
}
</script>

<style lang="scss" scoped>
.task-tag-select {
    width: 100%;
    display: flex;
    flex-direction: column;

    &.no-search {
        .search-box {
            display: none;
        }
        .tag-list {
            .tag-item {
                &:first-child {
                    margin-top: 0;
                }
            }
        }
    }

    .search-box {
        padding-bottom: 8px;
        border-bottom: 1px solid #eee;

        .search-input {
            width: 100%;
            height: 34px;
            padding: 0 12px;
            border: 1px solid #dcdfe6;
            border-radius: 4px;
            outline: none;

            &:focus {
                border-color: #84C56A;
            }
        }
    }

    .tag-list {
        flex: 1;
        overflow-y: auto;
        max-height: 300px;

        .tag-item {
            display: flex;
            align-items: flex-start;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 6px;
            margin-bottom: 6px;

            &:first-child {
                margin-top: 12px;
            }

            &:last-child {
                margin-bottom: 12px;
            }

            &:hover {
                background-color: #f5f7fa;
            }

            &.is-selected {
                background-color: #ecf5ff;
            }

            .tag-color {
                width: 16px;
                height: 16px;
                border-radius: 4px;
                margin-right: 8px;
                margin-top: 2px;
            }

            .tag-info {
                flex: 1;

                .tag-name {
                    line-height: 20px;
                    font-size: 14px;
                    color: #303133;
                }

                .tag-desc {
                    font-size: 12px;
                    color: #909399;
                    margin-top: 2px;
                }
            }

            .tag-check {
                color: #84C56A;
                margin-left: 12px;
                height: 20px;
                display: flex;
                align-items: center;
            }
        }

        .no-data {
            text-align: center;
            color: #909399;
            padding: 24px 0;
            margin-bottom: 12px;
        }
    }

    .footer-box {
        border-top: 1px solid #eee;
        padding-top: 8px;

        .add-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px 0 2px;
            cursor: pointer;
            color: #84C56A;
            border-radius: 6px;
            transition: color 0.2s;

            &:hover {
                color: #a2d98d;
            }

            i {
                margin-right: 4px;
            }
        }
    }
}
</style>
