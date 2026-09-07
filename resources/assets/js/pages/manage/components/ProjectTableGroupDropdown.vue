<template>
    <Dropdown
        :visible="open"
        @on-visible-change="open = $event"
        :trigger="touch ? 'click' : 'hover'"
        :placement="placement"
        transfer
        transfer-class-name="project-table-groups-dropdown">
        <div
            @keydown.enter.prevent="open = true"
            @keydown.space.prevent="open = true"
            @keydown.esc="open = false">
            <slot/>
        </div>
        <div slot="list" class="project-table-group-options" @click.stop @keydown.esc="open = false">
            <Checkbox
                v-for="group in groups"
                :key="group.key"
                class="ivu-dropdown-item"
                :class="{'ivu-dropdown-item-disabled': selected.length === 1 && selected.includes(group.key)}"
                :value="selected.includes(group.key)"
                :disabled="selected.length === 1 && selected.includes(group.key)"
                :title="selected.length === 1 && selected.includes(group.key) ? $L('至少保留一项') : ''"
                @on-change="$emit('change', group, $event)">
                {{$L(group.title)}}
            </Checkbox>
        </div>
    </Dropdown>
</template>

<script>
import {projectTableGroups} from "../../../utils/projectTableGroups";

export default {
    name: 'ProjectTableGroupDropdown',
    props: {
        value: {type: Boolean, default: false},
        touch: {type: Boolean, default: false},
        selected: {type: Array, required: true},
        placement: {type: String, default: 'bottom-start'},
    },
    computed: {
        groups() {
            return projectTableGroups;
        },
        open: {
            get() {
                return this.value;
            },
            set(value) {
                this.$emit('input', value);
            },
        },
    },
};
</script>
