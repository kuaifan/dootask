import fs from "fs";
import path from "path";
import {execSync} from "child_process";
import {defineConfig, loadEnv} from 'vite'
import {createVuePlugin} from 'vite-plugin-vue2';
import vitePluginRequire from 'vite-plugin-require'
import vitePluginFileCopy from 'vite-plugin-file-copy';
import autoprefixer from 'autoprefixer';
import chokidar from 'chokidar';

const argv = process.argv;
const basePath = argv.includes('electronBuild') ? './' : '/';
const publicPath = argv.includes('electronBuild') ? 'electron/public' : 'public';
const staticDir = {src: path.resolve(__dirname, 'resources/assets/statics/public'), dest: path.resolve(__dirname, publicPath)}

if (!argv.includes('fromcmd')) {
    execSync(`npx ${path.resolve(__dirname, 'cmd')} ${argv.includes("build") ? "build" : "dev"}`, {stdio: "inherit"});
    process.exit()
}

/**
 * 开发模式：由 nginx 把 /__vite/ 反代到本机 vite，页面与 vite 同源，任意访问地址/代理下均可用
 * - 监听成功后写入 nginx 反代片段并 reload，同时写 public/hot 通知后端走开发资源
 * - 退出时清理并 reload
 */
function devProxyPlugin(env, devBase) {
    const hotFile = path.resolve(__dirname, 'public/hot')
    const confFile = path.resolve(__dirname, 'docker/nginx/site/vite-dev.conf')
    const nginxName = `dootask-nginx-${env['APP_ID']}`
    const reloadNginx = () => {
        try {
            // nginx -s reload 在配置有误时也返回成功，先 -t 校验
            execSync(`docker exec ${nginxName} sh -c "nginx -t -q && nginx -s reload"`, {stdio: 'pipe'})
            return true
        } catch (e) {
            console.warn(`[dev-proxy] reload ${nginxName} failed: ${String(e.stderr || e.message).trim()}`)
            return false
        }
    }
    const cleanup = () => {
        const exists = fs.existsSync(confFile)
        fs.rmSync(hotFile, {force: true})
        fs.rmSync(confFile, {force: true})
        exists && reloadNginx()
    }
    return {
        name: 'dootask-dev-proxy',
        configureServer(server) {
            // 浏览器经 nginx 访问应用，不直接访问 vite，替换 vite 默认打印的地址
            server.printUrls = () => {
                const {info, warn} = server.config.logger
                if (!fs.existsSync(hotFile)) {
                    warn(`  > Proxy unavailable`)
                    return
                }
                // 与 vite 默认输出一致：地址青色、端口加粗
                const useColor = process.stdout.isTTY && !('NO_COLOR' in process.env)
                const ansi = (open, close) => text => useColor ? `\x1b[${open}m${text}\x1b[${close}m` : text
                const cyan = ansi(36, 39), bold = ansi(1, 22)
                const appPort = env['APP_PORT']
                info(`  > Local:    ${cyan(`http://localhost:${bold(appPort)}/`)}`)
                const proxyUri = process.env['VSCODE_PROXY_URI']
                if (proxyUri && proxyUri.includes('{{port}}')) {
                    info(`  > Network:  ${cyan(proxyUri.replace(/\{\{port\}\}/g, bold(appPort)))}`)
                }
            }
            server.httpServer.once('listening', () => {
                const {port} = server.httpServer.address()
                fs.writeFileSync(confFile, [
                    `location ^~ ${devBase} {`,
                    `    proxy_http_version 1.1;`,
                    `    proxy_set_header Host $host;`,
                    `    proxy_set_header Upgrade $http_upgrade;`,
                    `    proxy_set_header Connection $connection_upgrade;`,
                    `    proxy_read_timeout 1d;`,
                    `    proxy_buffering off;`,
                    // vite 开发服务不压缩，模块总量十几 MB，手机经公网加载时尤其慢
                    `    gzip on;`,
                    `    gzip_proxied any;`,
                    `    gzip_min_length 1024;`,
                    `    gzip_types application/javascript text/javascript text/css application/json;`,
                    `    proxy_pass http://host.docker.internal:${port};`,
                    `}`,
                    ``,
                ].join('\n'))
                if (reloadNginx()) {
                    fs.writeFileSync(hotFile, JSON.stringify({base: devBase, port}))
                } else {
                    // 不保留无效片段，否则 nginx 容器重启时会因配置错误起不来
                    fs.rmSync(confFile, {force: true})
                }
            })
            // 修改配置后 vite 会重新加载本文件并重启服务，退出钩子挂在 globalThis 上只注册一次
            if (!globalThis.__dootaskDevProxyHooked) {
                globalThis.__dootaskDevProxyHooked = true
                process.on('exit', cleanup)
                for (const signal of ['SIGINT', 'SIGHUP', 'SIGTERM']) {
                    process.on(signal, () => process.exit())
                }
            }
        },
        transform(code, id) {
            // vite 2 的 HMR 客户端固定连 location.hostname:端口，改为连当前页面的源（经 nginx 转发）
            if (id.includes('/vite/dist/client/client.mjs')) {
                return code.replace(
                    /const socketHost = `\$\{__HMR_HOSTNAME__ \|\| location\.hostname\}:\$\{__HMR_PORT__\}`;/,
                    `const socketHost = location.host + ${JSON.stringify(devBase)};`
                )
            }
        },
    }
}

export default defineConfig(({command, mode}) => {
    const env = loadEnv(mode, process.cwd(), '')
    const host = "0.0.0.0"
    const port = parseInt(env['APP_DEV_PORT']) || 5173
    const devBase = '/__vite/'

    const plugins = [
        createVuePlugin({
            template: {
                compilerOptions: {
                    isCustomElement: (tag) => tag.includes('micro-app'),
                }
            }
        }),
        vitePluginRequire(),
        vitePluginFileCopy([staticDir])
    ]
    if (command === 'serve') {
        plugins.push(devProxyPlugin(env, devBase))
    }
    if (mode === "development") {
        plugins.push({
            name: 'watch-copy',
            configureServer() {
                chokidar.watch(staticDir.src, {
                    ignoreInitial: true,
                }).on('all', (event, filePath) => {
                    if (['add', 'change', 'unlink'].includes(event)) {
                        const relativePath = path.relative(staticDir.src, filePath);
                        const destPath = path.resolve(staticDir.dest, relativePath);
                        if (event === 'unlink') {
                            if (fs.existsSync(destPath)) {
                                fs.unlinkSync(destPath);
                                console.log(`Removed ${destPath}`);
                            }
                            return;
                        }
                        if (path.basename(filePath) === '.DS_Store') {
                            return;
                        }
                        fs.mkdirSync(path.dirname(destPath), {recursive: true});
                        fs.copyFileSync(filePath, destPath);
                        console.log(`Copied ${filePath} to ${destPath}`);
                    }
                });
            }
        })
    }

    return {
        base: command === 'serve' ? devBase : basePath,
        publicDir: publicPath,
        server: {
            host,
            port,
            strictPort: false, // 端口被占用时自动顺延，实际端口由 devProxyPlugin 同步给 nginx
            watch: {
                ignored: [
                    '**/node_modules/**',
                    '**/storage/**',
                    '**/public/uploads/**',
                    '**/database/**',
                    '**/docker/**',
                    '**/tests/**',
                    '**/language/**',
                    '**/electron/**',
                ]
            },
        },
        resolve: {
            alias: {
                '~element-sea': path.resolve(__dirname, 'node_modules/element-sea'),
                '~quill-hi': path.resolve(__dirname, 'node_modules/quill-hi'),
                '~quill-mention-hi': path.resolve(__dirname, 'node_modules/quill-mention-hi'),
                '../images': path.resolve(__dirname, command === 'serve' ? '/images' : 'resources/assets/statics/public/images'),
                '../css': path.resolve(__dirname, command === 'serve' ? '/css' : 'resources/assets/statics/public/css')
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
        plugins,
        css: {
            postcss: {
                plugins: [
                    autoprefixer
                ]
            }
        }
    };
});
