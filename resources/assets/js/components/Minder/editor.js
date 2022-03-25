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
    assemble(require('vue-kityminder-ggg/src/runtime/container'));
    assemble(require('vue-kityminder-ggg/src/runtime/fsm'));
    assemble(require('vue-kityminder-ggg/src/runtime/minder'));
    assemble(require('vue-kityminder-ggg/src/runtime/receiver'));
    assemble(require('vue-kityminder-ggg/src/runtime/hotbox'));
    assemble(require('vue-kityminder-ggg/src/runtime/input'));
    assemble(require('vue-kityminder-ggg/src/runtime/clipboard-mimetype'));
    assemble(require('vue-kityminder-ggg/src/runtime/clipboard'));
    assemble(require('vue-kityminder-ggg/src/runtime/drag'));
    if (window.__minderReadOnly !== true) {
        assemble(require('vue-kityminder-ggg/src/runtime/node'));
        assemble(require('vue-kityminder-ggg/src/runtime/history'));
        assemble(require('vue-kityminder-ggg/src/runtime/jumping'));
        assemble(require('vue-kityminder-ggg/src/runtime/priority'));
        assemble(require('vue-kityminder-ggg/src/runtime/progress'));
    }
    return module.exports = KMEditor;
});
