<?php

namespace App\Module;

use Imagick;

/**
 * 图片类：缩略图、调整大小、压缩
 */
class Image
{
    private Imagick $image;

    private string $path;

    private int $height;

    private int $width;

    /**
     * @param $imagePath
     * @throws \ImagickException
     */
    public function __construct($imagePath) {
        $this->path = $imagePath;
        $this->image = new Imagick($this->path);
        $this->updateSize();
    }

    /**
     * 更新图片尺寸
     * @return void
     * @throws \ImagickException
     */
    private function updateSize() {
        $geo = $this->image->getImageGeometry();
        $this->height = $geo['height'];
        $this->width = $geo['width'];
    }

    /**
     * 获取图片宽度
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * 获取图片高度
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * 按比例裁剪
     * @param float $ratio
     * @return $this
     * @throws \ImagickException
     */
    public function ratioCrop(float $ratio = 0): static
    {
        if ($ratio === 0) {
            return $this;
        }
        if ($ratio  < 1) {
            $ratio = 1 / $ratio;
        }
        $width = $this->width;
        $height = $this->height;
        if ($width > $height * $ratio) {
            $newWidth = $height * $ratio;
            $newHeight = $height;
        } elseif ($height > $width * $ratio) {
            $newWidth = $width;
            $newHeight = $width * $ratio;
        } else {
            return $this;
        }
        $this->image->cropImage($newWidth, $newHeight, ($width - $newWidth) / 2, ($height - $newHeight) / 2);
        $this->updateSize();
        return $this;
    }

    /**
     * 创建缩略图
     * @param int $width
     * @param int $height
     * @param string $mode = 'percentage|cover|contain' ($width 和 $height 都设置的情况下有效)
     * @return Image
     * @throws \ImagickException
     */
    public function thumb(int $width, int $height, string $mode = 'percentage'): static
    {
        if ($height === 0 && $width === 0) {
            return $this;
        }
        if ($width && $height) {
            if ($mode === 'cover') {
                // 铺满背景（超出被裁掉）
                $this->image->cropThumbnailImage($width, $height);
            } else {
                // 等比缩放
                if ($this->width >= $this->height) {
                    $this->image->thumbnailImage($width, 0);
                } else {
                    $this->image->thumbnailImage(0, $height);
                }
                if ($mode === 'contain') {
                    // 显示完整的图
                    $background = new Imagick();
                    $background->newImage($width, $height, 'none', strtolower(pathinfo($this->path, PATHINFO_EXTENSION)));
                    $bg_width = $background->getImageWidth();
                    $bg_height = $background->getImageHeight();
                    $img_width = $this->image->getImageWidth();
                    $img_height = $this->image->getImageHeight();
                    if ($img_width / $bg_width > $img_height / $bg_height) {
                        $width = $bg_width;
                        $height = intval($img_height / ($img_width / $width));
                    } else {
                        $height = $bg_height;
                        $width = intval($img_width / ($img_height / $height));
                    }
                    $this->image->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1);
                    $x = intval(($bg_width - $width) / 2);
                    $y = intval(($bg_height - $height) / 2);
                    $background->compositeImage($this->image, Imagick::COMPOSITE_DEFAULT, $x, $y);
                    $this->image->destroy();
                    $this->image = $background;
                }
            }
        } else {
            $this->image->thumbnailImage($width, $height);
        }
        $this->updateSize();
        return $this;
    }

    /**
     * 调整图像大小
     * @param int $width
     * @param int $height
     * @return Image
     * @throws \ImagickException
     */
    public function resize(int $width, int $height): static
    {
        if ($height === 0 && $width === 0) {
            return $this;
        }
        $this->image->adaptiveResizeImage($width, $height);
        return $this;
    }

    /**
     * 压缩图片
     * @param int $quality
     * @return $this
     * @throws \ImagickException
     */
    public function compress(int $quality = 100): static
    {
        $format = strtolower($this->image->getImageFormat());
        switch ($format) {
            case 'jpeg':
            case 'jpg':
                $this->image->setImageCompression(Imagick::COMPRESSION_JPEG);
                break;
            case 'png':
                $this->image->setImageCompression(Imagick::COMPRESSION_ZIP);
                break;
            case 'gif':
                $this->image->setImageCompression(Imagick::COMPRESSION_LZW);
                break;
            default:
                return $this;
        }
        if ($quality > 1) {
            $this->image->setImageCompressionQuality($quality);
        } elseif ($quality > 0) {
            $this->image->setImageCompressionQuality($quality * 100);
        } else {
            $this->image->setImageCompressionQuality(100);
        }
        return $this;
    }

    /**
     * 保存结果到文件
     * @param string $savePath Save path
     * @return void
     * @throws \ImagickException
     */
    public function saveTo(string $savePath): void
    {
        $this->image->writeImage($savePath);
        $this->image->destroy();
    }

    /**
     * 销毁对象
     * @return void
     */
    public function destroy()
    {
        $this->image->destroy();
    }

    /** ******************************************************************************/
    /** ******************************************************************************/
    /** ******************************************************************************/

    /**
     * 生成缩略图
     * @param string $imagePath 图片路径
     * @param string $savePath  保存路径
     * @param int $width        宽度
     * @param int $height       高度
     * @param int $quality      压缩质量（0-100）, 0 为不压缩
     * @param string $mode      模式（percentage|cover|contain）
     * @return string|null      成功返回图片后缀，失败返回 false
     */
    public static function thumbImage(string $imagePath, string $savePath, int $width, int $height, int $quality = 0, string $mode = 'percentage'): ?string
    {
        if (!file_exists($imagePath)) {
            return null;
        }
        try {
            $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
            if (str_contains($savePath, '.{*}')) {
                $savePath = str_replace('.{*}', '.' . $extension, $savePath);
            }
            $image = new Image($imagePath);
            $image->thumb($width, $height, $mode);
            $image->saveTo($savePath);
            if ($quality > 0) {
                Image::compressImage($savePath, null, $quality);
            }
            if ($savePath != $imagePath && filesize($savePath) >= filesize($imagePath)) {
                unlink($savePath);
                symlink(basename($imagePath), $savePath);
            }
            return $extension;
        } catch (\ImagickException) {
            return null;
        }
    }

    /**
     * 压缩图片（如果压缩后的图片比原图还大那就直接使用原图）
     * @param string $imagePath     图片路径
     * @param string|null $savePath 保存路径（默认覆盖原图）
     * @param int $quality          压缩质量（0-100）
     * @param float $minSize        最小尺寸，小于这个尺寸不压缩（单位：KB）
     * @return bool
     */
    public static function compressImage(string $imagePath, string $savePath = null, int $quality = 100, float $minSize = 5): bool
    {
        if (Base::settingFind("system", "image_compress") === 'close') {
            return false;
        }
        if (!file_exists($imagePath)) {
            return false;
        }
        $quality = min(max($quality, 1), 100);
        $imageSize = filesize($imagePath);
        if ($minSize > 0 && $imageSize < $minSize * 1024) {
            return false;
        }
        if (empty($savePath)) {
            $savePath = $imagePath;
        }
        $tmpPath = $imagePath . '.compress.tmp';
        if (self::compressAuto($imagePath, $tmpPath, $quality)) {
            if (filesize($tmpPath) >= $imageSize) {
                copy($imagePath, $savePath);
                unlink($tmpPath);
            } else {
                rename($tmpPath, $savePath);
            }
            return true;
        }
        return false;
    }

    /**
     * 自动压缩图片（仅限于compressImage方法使用）
     * @param string $imagePath
     * @param string $savePath
     * @param int $quality
     * @return bool
     */
    private static function compressAuto(string $imagePath, string $savePath, int $quality = 100): bool
    {
        if (strtolower(pathinfo($imagePath, PATHINFO_EXTENSION)) === 'png') {
            $minQuality = $quality - 20;
            $compressedContent = shell_exec("pngquant --quality={$minQuality}-{$quality} --strip - < " . $imagePath);
            if ($compressedContent) {
                file_put_contents($savePath, $compressedContent);
                return true;
            }
        }
        try {
            $image = new Image($imagePath);
            $image->compress($quality);
            $image->saveTo($savePath);
            return true;
        } catch (\ImagickException) {
            return false;
        }
    }

    /** ******************************************************************************/
    /** ******************************************************************************/
    /** ******************************************************************************/

    // ImageMagick 策略限制配置
    private static $limits = [
        'width' => 16384,    // 16KP
        'height' => 16384,   // 16KP
        'area' => 128000000, // 128MP (128 * 1000000 pixels)
        'memory' => 256,     // 256MiB
    ];

    /**
     * 验证上传的图片
     * @param $file
     * @return array
     */
    public static function validateImage($file)
    {
        try {
            // 获取图片信息
            $imageInfo = getimagesize($file);
            if ($imageInfo === false) {
                return Base::retError('无法获取图片信息');
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $area = $width * $height;

            // 检查尺寸限制
            if ($width > self::$limits['width']) {
                return Base::retError(sprintf('图片宽度(%dpx)超过限制(%dpx)', $width, self::$limits['width']));
            }

            if ($height > self::$limits['height']) {
                return Base::retError(sprintf('图片高度(%dpx)超过限制(%dpx)', $height, self::$limits['height']));
            }

            if ($area > self::$limits['area']) {
                return Base::retError(sprintf('图片总像素(%dpx)超过限制(%dpx)', $area, self::$limits['area']));
            }

            // 估算内存使用（每个像素约4字节）
            $estimatedMemory = ($area * 4) / (1024 * 1024); // 转换为 MB
            if ($estimatedMemory > self::$limits['memory']) {
                return Base::retError(sprintf('预计内存使用(%dMB)超过限制(%dMB)', $estimatedMemory, self::$limits['memory']));
            }

            return Base::retSuccess('success');
        } catch (\Exception $e) {
            return Base::retError('验证过程发生错误：' . $e->getMessage());
        }
    }
}
