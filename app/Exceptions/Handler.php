<?php

namespace App\Exceptions;

use App\Module\Base;
use App\Module\Image;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * 将异常转换为 HTTP 响应。
     * @param $request
     * @param Throwable $e
     * @return array|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
     * @throws Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof NotFoundHttpException) {
            if ($result = $this->ImagePathHandler($request)) {
                return $result;
            }
        }
        if ($e instanceof ApiException) {
            return response()->json(Base::retError($e->getMessage(), $e->getData(), $e->getCode()));
        } elseif ($e instanceof ModelNotFoundException) {
            return response()->json(Base::retError('Interface error'));
        }
        return parent::render($request, $e);
    }

    /**
     * 重写report优雅记录
     * @param Throwable $e
     * @throws Throwable
     */
    public function report(Throwable $e)
    {
        if ($e instanceof ApiException) {
            if ($e->getCode() !== -1) {
                Log::error($e->getMessage(), [
                    'code' => $e->getCode(),
                    'data' => $e->getData(),
                    'exception' => ' at ' . $e->getFile() . ':' . $e->getLine()
                ]);
            }
        } else {
            parent::report($e);
        }
    }

    /**
     * 图片路径处理
     * @param $request
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|null
     */
    private function ImagePathHandler($request)
    {
        $path = $request->path();

        // 处理图片
        $patternCrop = '/^(uploads\/.*\.(png|jpg|jpeg))\/crop\/([^\/]+)$/';
        $patternThumb = '/^(uploads\/.*)_thumb\.(png|jpg|jpeg)$/';
        $matchesCrop = null;
        $matchesThumb = null;
        if (preg_match($patternCrop, $path, $matchesCrop) || preg_match($patternThumb, $path, $matchesThumb)) {
            // 获取参数
            if ($matchesCrop) {
                $file = $matchesCrop[1];
                $ext = $matchesCrop[2];
                $rules = preg_replace('/\s+/', '', $matchesCrop[3]);
                $rules = str_replace(['=', '&'], [':', ','], $rules);
                $rules = explode(',', $rules);
            } elseif ($matchesThumb) {
                $file = $matchesThumb[1];
                $ext = $matchesThumb[2];
                $rules = ['percentage:320x0'];
            } else {
                return null;
            }
            if (empty($rules)) {
                return null;
            }

            // 提取年月
            $Ym = date("Ym");
            if (preg_match('/\/(\d{6})\//', $file, $ms)) {
                $Ym = $ms[1];
            }

            // 文件存在直接返回
            $dirName = str_replace(['/', '.'], '_', $file);
            $fileName = str_replace([':', ','], ['-', '_'], implode(',', $rules)) . '.' . $ext;
            $savePath = public_path('uploads/tmp/crop/' . $Ym . '/' . $dirName . '/' . $fileName);
            if (file_exists($savePath)) {
                // 设置头部声明图片缓存
                return response()->file($savePath, [
                    'Pragma' => 'public',
                    'Cache-Control' => 'max-age=1814400',
                    'Expires' => gmdate('D, d M Y H:i:s', time() + 1814400) . ' GMT',
                    'Last-Modified' => gmdate('D, d M Y H:i:s', filemtime($savePath)) . ' GMT',
                    'ETag' => md5_file($savePath)
                ]);
            }

            // 文件不存在处理
            $sourcePath = public_path($file);
            if (!file_exists($sourcePath)) {
                return null;
            }

            // 判断删除多余文件
            $saveDir = dirname($savePath);
            if (is_dir($saveDir)) {
                $items = glob($saveDir . '/*');
                if (count($items) > 5) {
                    usort($items, function ($a, $b) {
                        return filemtime($b) - filemtime($a);
                    });
                    $itemsToDelete = array_slice($items, 5);
                    foreach ($itemsToDelete as $item) {
                        if (is_file($item)) {
                            unlink($item);
                        }
                    }
                }
            } else {
                Base::makeDir($saveDir);
            }

            // 处理图片
            try {
                $handle = 0;
                $image = new Image($sourcePath);
                foreach ($rules as $rule) {
                    if (!str_contains($rule, ':')) {
                        continue;
                    }
                    [$type, $value] = explode(':', $rule);
                    if (!in_array($type, ['ratio', 'size', 'percentage', 'cover', 'contain'])) {
                        continue;
                    }
                    switch ($type) {
                        // 按比例裁剪
                        case 'ratio':
                            if (is_numeric($value)) {
                                $image->ratioCrop($value);
                                $handle++;
                            }
                            break;

                        // 按尺寸缩放
                        case 'size':
                            $size = Base::newIntval(explode('x', $value));
                            if (count($size) === 2) {
                                $image->resize($size[0], $size[1]);
                                $handle++;
                            }
                            break;

                        // 按尺寸缩放
                        case 'percentage':
                        case 'cover':
                        case 'contain':
                            $size = Base::newIntval(explode('x', $value));
                            if (count($size) === 2) {
                                $image->thumb($size[0], $size[1], $type);
                                $handle++;
                            }
                            break;
                    }
                }
                if ($handle > 0) {
                    $image->saveTo($savePath);
                    Image::compressImage($savePath, 80);
                    return response()->file($savePath, [
                        'Pragma' => 'public',
                        'Cache-Control' => 'max-age=1814400',
                        'Expires' => gmdate('D, d M Y H:i:s', time() + 1814400) . ' GMT',
                        'Last-Modified' => gmdate('D, d M Y H:i:s', filemtime($savePath)) . ' GMT',
                        'ETag' => md5_file($savePath)
                    ]);
                } else {
                    $image->destroy();
                }
            } catch (\ImagickException) { }
        }

        // 容错处理
        $patternFault = '/^(images\/.*\.(png|jpg|jpeg))\/crop\/([^\/]+)$/';
        $matchesFault = null;
        if (preg_match($patternFault, $path, $matchesFault)) {
            $file = public_path($matchesFault[1]);
            if (!file_exists($file)) {
                $file = public_path('images/other/imgerr.jpg');
            }
            if (file_exists($file)) {
                return response()->file($file);
            }
        }

        return null;
    }
}
