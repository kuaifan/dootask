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

    /** ******************************************************************************/
    /** ******************************************************************************/
    /** ******************************************************************************/

    /**
     * 生成缩略图
     * @param string $imagePath 图片路径
     * @param string $savePath  保存路径
     * @param int $width        宽度
     * @param int $height       高度
     * @param string $mode      模式（percentage|cover|contain）
     * @return string|null      成功返回图片后缀，失败返回 false
     */
    public static function thumbImage(string $imagePath, string $savePath, int $width, int $height, string $mode = 'percentage'): ?string
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
            return $extension;
        } catch (\ImagickException) {
            return null;
        }
    }

    /**
     * 压缩图片
     * @param string $imagePath     图片路径
     * @param string|null $savePath 保存路径（默认覆盖原图）
     * @param int $quality          压缩质量（0-100）
     * @param float $minSize        最小尺寸（单位：KB）
     * @return bool
     */
    public static function compressImage(string $imagePath, string $savePath = null, int $quality = 100, float $minSize = 10): bool
    {
        if (Base::settingFind("system", "image_compress") === 'close') {
            return false;
        }
        if (!file_exists($imagePath)) {
            return false;
        }
        $imageSize = filesize($imagePath);
        if ($minSize > 0 && $imageSize < $minSize * 1024) {
            return false;
        }
        if (empty($savePath)) {
            $savePath = $imagePath;
        }
        $tmpPath = $imagePath . '.compress.tmp';
        try {
            $image = new Image($imagePath);
            $image->compress($quality);
            $image->saveTo($tmpPath);
            if (filesize($tmpPath) >= $imageSize) {
                copy($imagePath, $savePath);
                unlink($tmpPath);
            } else {
                rename($tmpPath, $savePath);
            }
            return true;
        } catch (\ImagickException) {
            return false;
        }
    }
}
