<template>
    <Dropdown
        :visible="open"
        @on-visible-change="open = $event"
        :trigger="touch ? 'click' : 'hover'"
        :placement="placement"
        transfer
        transfer-class-name="project-flow-filter-dropdown">
        <div
            @keydown.enter.prevent="open = true"
            @keydown.space.prevent="open = true"
            @keydown.esc="open = false">
            <slot/>
        </div>
        <div slot="list" class="project-flow-filter" @click.stop @keydown.esc="open = false">
            <div class="flow-filter-head">
                <span class="flow-filter-count">{{total > 0 ? $L('已选(*)项', total) : $L('未筛选')}}</span>
                <span :class="['flow-filter-clear', {disabled: total === 0}]" @click="total > 0 && $emit('clear')">{{$L('清空')}}</span>
            </div>
            <div class="flow-filter-body">
                <div v-for="group in groups" :key="group.key" class="flow-filter-group">
                    <div class="flow-filter-group-title">
                        <span>{{$L(group.title)}}<template v-if="selected[group.key].length > 0"> · {{selected[group.key].length}}</template></span>
                        <span v-if="selected[group.key].length > 0" class="flow-filter-group-clear" @click="$emit('clear', group.key)">{{$L('清除')}}</span>
                    </div>
                    <div v-if="group.searchable" class="flow-filter-search">
                        <Input v-model="keyword" size="small" clearable :placeholder="$L('搜索成员')"/>
                    </div>
                    <div v-for="(section, index) in filterSections(group)" :key="index">
                        <div v-if="section.title" class="flow-filter-section-title">{{section.title}}</div>
                        <div
                            v-for="item in section.items"
                            :key="item.value"
                            :class="['flow-filter-item', item.status]"
                            :style="item.style"
                            @click="$emit('change', group.key, item, !isSelected(group.key, item.value))">
                            <Checkbox :value="isSelected(group.key, item.value)" class="flow-filter-checkbox"/>
                            <span v-if="item.color" class="flow-filter-dot" :style="{backgroundColor: item.color}"></span>
                            <UserAvatar v-else-if="item.userid" :userid="item.userid" :size="20"/>
                            <span class="flow-filter-name">{{item.label}}</span>
                            <span class="flow-filter-num">{{item.count}}</span>
                        </div>
                    </div>
                    <div v-if="group.searchable && filterSections(group).length === 0" class="flow-filter-empty">{{$L('无匹配成员')}}</div>
                </div>
            </div>
        </div>
    </Dropdown>
</template>

<script>
export default {
    name: 'ProjectFlowFilterDropdown',
    props: {
        value: {type: Boolean, default: false},
        touch: {type: Boolean, default: false},
        groups: {type: Array, required: true},
        selected: {type: Object, required: true},
        total: {type: Number, default: 0},
        placement: {type: String, default: 'bottom-end'},
    },
    data() {
        return {
            keyword: '',
        };
    },
    computed: {
        open: {
            get() {
                return this.value;
            },
            set(value) {
                this.$emit('input', value);
            },
        },
    },
    methods: {
        isSelected(key, value) {
            return this.selected[key].includes(value);
        },
        filterSections(group) {
            // 搜索框隐藏时（人数降到阈值以下）忽略残留关键词
            const keyword = this.keyword.trim();
            if (!group.searchable || !keyword) {
                return group.sections;
            }
            return group.sections.map(section => ({
                ...section,
                items: section.items.filter(item => $A.strExists(item.label, keyword)),
            })).filter(section => section.items.length > 0);
        },
    },
};
</script>
