export default {
    projectChatShowToggle(state) {
        state.projectChatShow = !state.projectChatShow
        state.setStorage('projectChatShow', state.projectChatShow);
    }
}
