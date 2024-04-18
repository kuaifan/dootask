import {resolve} from "path";
import {writeFileSync, existsSync, unlinkSync} from "fs";
import {execSync} from "child_process";
import {defineConfig, loadEnv} from 'vite'
import {createVuePlugin} from 'vite-plugin-vue2';
import vitePluginRequire from 'vite-plugin-require'
import vitePluginFileCopy from 'vite-plugin-file-copy';
import autoprefixer from 'autoprefixer';

const argv = process.argv;
const basePath = argv.includes('electronBuild') ? './' : '/';
const publicPath = argv.includes('electronBuild') ? 'electron/public' : 'public';

if (!argv.includes('fromcmd')) {
    execSync(`npx ${resolve(__dirname, 'cmd')} ${argv.includes("build") ? "build" : "dev"}`, {stdio: "inherit"});
    process.exit()
}

export default defineConfig(({command, mode}) => {
    const env = loadEnv(mode, process.cwd(), '')
    const host = "0.0.0.0"
    const port = parseInt(env['APP_DEV_PORT'])

    if (command === 'serve') {
        const hotFile = resolve(__dirname, 'public/hot')
        const hotClean = (exit) => {
            if (existsSync(hotFile)) {
                unlinkSync(hotFile);
            }
            if (exit) {
                process.exit()
            }
        }
        hotClean(false)
        writeFileSync(hotFile, JSON.stringify(env));
        process.on('exit', () => hotClean(true));
        process.on('SIGINT', () => hotClean(true));
        process.on('SIGHUP', () => hotClean(true));
    }

    return {
        base: basePath,
        publicDir: publicPath,
        server: {
            host,
            port,
            strictPort: false,
        },
        resolve: {
            alias: {
                '~element-sea': resolve(__dirname, 'node_modules/element-sea'),
                '~quill-hi': resolve(__dirname, 'node_modules/quill-hi'),
                '~quill-mention-hi': resolve(__dirname, 'node_modules/quill-mention-hi'),
                '../images': resolve(__dirname, command === 'serve' ? '/images' : 'resources/assets/statics/public/images'),
                '../css': resolve(__dirname, command === 'serve' ? '/css' : 'resources/assets/statics/public/css')
            },
            extensions: ['.mjs', '.js', '.ts', '.jsx', '.tsx', '.json', '.vue']
        },
        build: {
            manifest: true,
            outDir: publicPath,
            assetsDir: "js/build",
            emptyOutDir: false,
            rollupOptions: {
                input: 'resources/assets/js/app.js',
                output: {
                    manualChunks(id) {
                        if (id.includes('node_modules')) {
                            return id.toString().split('node_modules/')[1].split('/')[0].toString();
                        }
                    }
                }
            },
            brotliSize: false,
            chunkSizeWarningLimit: 1500,
        },
        plugins: [
            createVuePlugin({
                template: {
                    compilerOptions: {
                        isCustomElement: (tag) => tag.includes('micro-app') ,
                    }
                }
            }),
            vitePluginRequire(),
            vitePluginFileCopy([{
                src: resolve(__dirname, 'resources/assets/statics/public'),
                dest: resolve(__dirname, publicPath)
            }]),
        ],
        css: {
            postcss: {
                plugins: [
                    autoprefixer
                ]
            }
        }
    };
});
