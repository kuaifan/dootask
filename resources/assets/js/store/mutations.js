export default {
    projectChatShowToggle(state) {
        state.projectChatShow = !state.projectChatShow
        state.setStorage('projectChatShow', state.projectChatShow);
    },
    userInfo(state, info) {
        state.userInfo = info
        state.setStorage('userInfo', info);
        state.setStorage('token', state._isJson(info) ? info.token : '');
    }
}
