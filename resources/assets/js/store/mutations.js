export default {
    toggleProjectChatShow(state) {
        state.projectChatShow = !state.projectChatShow
        state.setStorage('projectChatShow', state.projectChatShow);
    },
    setUserInfo(state, info) {
        const userInfo = state._cloneJSON(info);
        userInfo.userid = state._runNum(userInfo.userid);
        userInfo.token = userInfo.userid > 0 ? (userInfo.token || state.userToken) : '';
        state.userInfo = userInfo;
        state.userToken = userInfo.token;
        state.setStorage('userInfo', state.userInfo);
    }
}
