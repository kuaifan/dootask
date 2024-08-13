import Vue from 'vue';
import Emoji from "./emoji.vue";
import {Modal} from "view-design-hi";

const inputLoadUid = {}

function inputLoadAdd(dialogId, uid) {
    if (!dialogId || typeof inputLoadUid[dialogId] === "undefined") {
        inputLoadUid[dialogId] = [];
    } else {
        inputLoadUid[dialogId] = inputLoadUid[dialogId].filter(v => v !== uid)
    }
    inputLoadUid[dialogId].push(uid)
}

function inputLoadRemove(dialogId, uid) {
    if (!dialogId || typeof inputLoadUid[dialogId] === "undefined") {
        return;
    }
    inputLoadUid[dialogId] = inputLoadUid[dialogId].filter(v => v !== uid)
}

function inputLoadIsLast(dialogId, uid) {
    if (typeof inputLoadUid[dialogId] === "undefined") {
        return false;
    }
    return inputLoadUid[dialogId][inputLoadUid[dialogId].length - 1] === uid
}

function choiceEmojiOne() {
    return new Promise(resolve => {
        const Instance = new Vue({
            render (h) {
                return h(Modal, {
                    class: 'chat-emoji-one-modal',
                    props: {
                        fullscreen: true,
                        footerHide: true,
                    },
                    on: {
                        'on-visible-change': (v) => {
                            if (!v) {
                                setTimeout(_ => {
                                    document.body.removeChild(this.$el);
                                }, 500)
                            }
                        }
                    }
                }, [
                    h(Emoji, {
                        attrs: {
                            onlyEmoji: true
                        },
                        on: {
                            'on-select': (item) => {
                                this.$children[0].visible = false
                                if (item.type === 'emoji') {
                                    resolve(item.text)
                                }
                            },
                        }
                    })
                ]);
            }
        });

        const component = Instance.$mount();
        document.body.appendChild(component.$el);

        const modal = Instance.$children[0];
        modal.visible = true;

        modal.$el.lastChild.addEventListener("click", ({target}) => {
            if (target.classList.contains("ivu-modal-body")) {
                modal.visible = false
            }
        });
    })
}

export {choiceEmojiOne, inputLoadAdd, inputLoadRemove, inputLoadIsLast}
