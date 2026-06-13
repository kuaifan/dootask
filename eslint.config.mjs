import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';

// 起步策略:只开能抓真实 bug 的规则,存量代码必须 0 error;
// 风格类/噪声规则后续按需逐步收紧。
export default [
    {
        ignores: [
            'node_modules/**',
            'vendor/**',
            'public/**',
            'electron/**',
            'docker/**',
            'resources/assets/statics/**',
            // 第三方移植的 directive,内含过期的 babel/* eslint 注释
            'resources/assets/js/directives/v-click-outside-x.js',
        ],
    },
    ...pluginVue.configs['flat/vue2-essential'],
    {
        files: ['resources/assets/js/**/*.{js,mjs,vue}', 'scripts/**/*.mjs'],
        linterOptions: {
            reportUnusedDisableDirectives: 'off',
        },
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node,
                $A: 'readonly',
                $L: 'readonly',
                $: 'readonly',
                jQuery: 'readonly',
                LANGUAGE_DATA: 'readonly',
                SystemConfig: 'readonly',
            },
        },
        rules: {
            'no-dupe-keys': 'error',
            'no-dupe-args': 'error',
            'no-const-assign': 'error',
            'no-class-assign': 'error',
            'no-compare-neg-zero': 'error',
            'no-self-assign': 'error',
            'use-isnan': 'error',
            'valid-typeof': 'error',
            // 以下规则存量代码有违规,先降为 warn 保持可见;清零后逐条升回 error
            'no-unreachable': 'warn',
            'no-cond-assign': 'warn',
            'vue/multi-word-component-names': 'off',
            'vue/require-v-for-key': 'warn',
            'vue/no-use-v-if-with-v-for': 'warn',
            'vue/valid-template-root': 'warn',
            'vue/valid-v-for': 'warn',
            'vue/no-unused-components': 'warn',
            'vue/no-mutating-props': 'warn',
            'vue/no-unused-vars': 'warn',
            'vue/no-textarea-mustache': 'warn',
            'vue/no-reserved-keys': 'warn',
            'vue/no-side-effects-in-computed-properties': 'warn',
            'vue/no-v-text-v-html-on-component': 'warn',
            'vue/require-valid-default-prop': 'warn',
            'vue/valid-v-show': 'warn',
        },
    },
];
