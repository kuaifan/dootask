import {resolve} from "path";
import {defineConfig} from 'vite'
import {createVuePlugin} from 'vite-plugin-vue2';
import vitePluginRequire from 'vite-plugin-require'
import vitePluginFileCopy from 'vite-plugin-file-copy';

const argv = process.argv;
const isElectron = argv.includes('--electron');
const publicPath = isElectron ? 'electron/public' : 'public';

const serverHost = '0.0.0.0'
const serverPort = 22222

export default defineConfig(({command, mode}) => {
    return {
        base: '/',
        publicDir: publicPath,
        server: {
            host: serverHost,
            port: serverPort,
        },
        resolve: {
            alias: {
                '~element-ui': resolve(__dirname, './node_modules/element-ui'),
                '~quill': resolve(__dirname, './node_modules/quill'),
                '~quill-mention-hi': resolve(__dirname, './node_modules/quill-mention-hi'),
                '../images': resolve(__dirname, command === 'serve' ? '/images' : './resources/assets/statics/public/images'),
                '../css': resolve(__dirname, command === 'serve' ? '/css' : './resources/assets/statics/public/css')
            },
            extensions: ['.mjs', '.js', '.ts', '.jsx', '.tsx', '.json', '.vue']
        },
        define: {
            'process.env.NODE_ENV': command === 'serve' ? '"development"' : '"production"',
        },
        build: {
            manifest: true,
            outDir: publicPath,
            assetsDir: "js/build",
            rollupOptions: {
                input: 'resources/assets/js/app.js',
            },
            chunkSizeWarningLimit: 3000,
        },
        plugins: [
            createVuePlugin(),
            vitePluginRequire(),
            vitePluginFileCopy([{src: 'resources/assets/statics/public', dest: publicPath}]),
        ]
    };
});
