<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;

class DocApiMap extends Command
{
    protected $signature = 'doc:api-map';
    protected $description = '生成 API 路由对照表（routes/api-map.md）';

    public function handle(): int
    {
        $controllers = $this->collectControllers();
        if (empty($controllers)) {
            $this->error('未从路由中解析到任何 api 控制器');
            return 1;
        }

        $total = 0;
        $sections = [];
        foreach ($controllers as $prefix => $controller) {
            $rows = $this->collectMethods($prefix, $controller['class'], $controller['fixed_method']);
            $total += count($rows);
            $sections[] = $this->renderSection($prefix, $controller['class'], $rows);
        }

        $path = base_path('routes/api-map.md');
        file_put_contents($path, $this->renderHeader($total) . implode("\n", $sections));

        $this->info("已生成: routes/api-map.md（控制器 " . count($controllers) . " 个，接口 {$total} 个）");
        return 0;
    }

    /**
     * 从已注册路由中收集 api 前缀与控制器的映射
     * 匹配 routes/web.php 中的动态路由：api/{prefix}/{method}
     * 同时支持固定 method 的三级路由，如 api/file/dav/{action}。
     * @return array [prefix => ['class' => 控制器类名, 'fixed_method' => 固定方法名|null]]
     */
    private function collectControllers(): array
    {
        $controllers = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            $fixedMethod = null;
            if (preg_match('/^api\/(.+)\/\{method}$/', $uri, $match)) {
                $prefix = $match[1];
            } elseif (preg_match('/^api\/(.+)\/\{action}$/', $uri, $match) && isset($route->defaults['method'])) {
                $prefix = $match[1];
                $fixedMethod = (string) $route->defaults['method'];
            } else {
                continue;
            }
            $class = $route->getAction('controller');
            if ($class && class_exists($class)) {
                $controllers[$prefix] = [
                    'class' => $class,
                    'fixed_method' => $fixedMethod,
                ];
            }
        }
        return $controllers;
    }

    /**
     * 反射收集控制器的接口方法
     * @param string $prefix 路由前缀（如 project）
     * @param string $class 控制器类名
     * @return array [['url' => ..., 'method' => ..., 'http' => ..., 'title' => ...], ...]
     */
    private function collectMethods(string $prefix, string $class, ?string $fixedMethod): array
    {
        $rows = [];
        $reflection = new ReflectionClass($class);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // 仅保留本类声明的实例方法，排除 __invoke/__before/__construct 等魔术/框架方法
            if ($method->getDeclaringClass()->getName() !== $class
                || $method->isStatic()
                || str_starts_with($method->getName(), '__')) {
                continue;
            }
            $methodName = $method->getName();
            if ($fixedMethod !== null) {
                $fixedPrefix = $fixedMethod . '__';
                if (!str_starts_with($methodName, $fixedPrefix)) {
                    continue;
                }
                $urlMethod = substr($methodName, strlen($fixedPrefix));
            } else {
                $urlMethod = str_replace('__', '/', $methodName);
            }
            [$http, $title] = $this->parseApiDoc($method);
            $rows[] = [
                'url' => "api/{$prefix}/{$urlMethod}",
                'method' => $methodName . '()',
                'http' => $http,
                'title' => $title,
            ];
        }
        return $rows;
    }

    /**
     * 解析方法 docblock 中的 @api 注释行
     * 格式如：@api {get} api/project/lists 获取项目列表
     * @return array [HTTP 方法, 标题]，无 @api 注释时为 ['any', '']
     */
    private function parseApiDoc(ReflectionMethod $method): array
    {
        $doc = $method->getDocComment();
        if ($doc && preg_match('/@api\s+\{(\w+)}\s+(\S+)(?:[ \t]+(.+))?/', $doc, $match)) {
            return [strtolower($match[1]), trim($match[3] ?? '')];
        }
        return ['any', ''];
    }

    /**
     * 生成文件头说明
     */
    private function renderHeader(int $total): string
    {
        return <<<MD
            # API 路由对照表

            > 此文件由 `php artisan doc:api-map` 生成，勿手改。

            接口总数：{$total}

            ## 路由规则

            API 使用动态路由（见 `routes/web.php`），URL 段映射为控制器方法名：

            - `api/{controller}/{method}` → `{method}()`，如 `api/project/lists` → `ProjectController::lists()`
            - `api/{controller}/{method}/{action}` → `{method}__{action}()`（双下划线连接），如 `api/project/invite/join` → `ProjectController::invite__join()`
            - 路由最多两段，方法名最多一个双下划线


            MD;
    }

    /**
     * 生成单个控制器的表格段落
     */
    private function renderSection(string $prefix, string $class, array $rows): string
    {
        $short = class_basename($class);
        $lines = [
            "## {$prefix}（{$short}）",
            '',
            '| URL | 方法名 | HTTP | 说明 |',
            '| --- | --- | --- | --- |',
        ];
        foreach ($rows as $row) {
            $lines[] = "| {$row['url']} | {$row['method']} | {$row['http']} | {$row['title']} |";
        }
        $lines[] = '';
        return implode("\n", $lines);
    }
}
