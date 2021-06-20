export default {
    /**
     * 会话消息列表
     * @param state
     * @param data
     */
    dialogMsgListSuccess(state, data) {
        const dialog = data.dialog;
        const list = data.data;
        // 更新当前会话消息
        if (state.dialogId == dialog.id) {
            state.dialogDetail = dialog;
            list.forEach((item) => {
                let index = state.dialogMsgList.findIndex(({id}) => id == item.id);
                if (index === -1) {
                    state.dialogMsgList.unshift(item);
                } else {
                    state.dialogMsgList.splice(index, 1, item);
                }
            });
            this.commit("dialogMsgListStorageCurrent");
        }
        // 页数数据
        state.dialogMsgCurrentPage = data.current_page;
        state.dialogMsgHasMorePages = data.current_page < data.last_page;
        // 更新会话数据
        this.dispatch("saveDialog", dialog);
    },

    /**
     * 保存当前会话消息
     * @param state
     */
    dialogMsgListStorageCurrent(state) {
        if (!state.method.isJson(state.cacheDialogMsg[state.dialogId])) {
            return;
        }
        //
        state.cacheDialogMsg[state.dialogId].data = state.dialogMsgList;
        state.method.setStorage("cacheDialogMsg", state.cacheDialogMsg);
    },

    /**
     * 将会话移动到首位
     * @param state
     * @param dialog_id
     */
    dialogMoveToTop(state, dialog_id) {
        const index = state.dialogList.findIndex(({id}) => id == dialog_id);
        if (index > -1) {
            const tmp = state.method.cloneJSON(state.dialogList[index]);
            state.dialogList.splice(index, 1);
            state.dialogList.unshift(tmp);
        }
    }
}
