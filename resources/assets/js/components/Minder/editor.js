define(function(require, exports, module) {

    /**
     * 运行时
     */
    var runtimes = [];

    function assemble(runtime) {
        runtimes.push(runtime);
    }

    function KMEditor(selector) {
        this.selector = selector;
        for (var i = 0; i < runtimes.length; i++) {
            if (typeof runtimes[i] == 'function') {
                runtimes[i].call(this, this);
            }
        }
    }
    KMEditor.assemble = assemble;
    assemble(require('vue-kityminder-gg/src/runtime/container'));
    assemble(require('vue-kityminder-gg/src/runtime/fsm'));
    assemble(require('vue-kityminder-gg/src/runtime/minder'));
    assemble(require('vue-kityminder-gg/src/runtime/receiver'));
    assemble(require('vue-kityminder-gg/src/runtime/hotbox'));
    assemble(require('vue-kityminder-gg/src/runtime/input'));
    assemble(require('vue-kityminder-gg/src/runtime/clipboard-mimetype'));
    assemble(require('vue-kityminder-gg/src/runtime/clipboard'));
    assemble(require('vue-kityminder-gg/src/runtime/drag'));
    if (window.__minderReadOnly !== true) {
        assemble(require('vue-kityminder-gg/src/runtime/node'));
        assemble(require('vue-kityminder-gg/src/runtime/history'));
        assemble(require('vue-kityminder-gg/src/runtime/jumping'));
        assemble(require('vue-kityminder-gg/src/runtime/priority'));
        assemble(require('vue-kityminder-gg/src/runtime/progress'));
    }
    return module.exports = KMEditor;
});
