<?php

namespace App\Module;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

/**
 * 图片压缩类：通过缩放来压缩。
 * 如果要保持源图比例，把参数$percent保持为1即可。
 * 即使原比例压缩，也可大幅度缩小。数码相机4M图片。也可以缩为700KB左右。如果缩小比例，则体积会更小。
 *
 * 结果：可保存、可直接显示。
 */
class ImgCompress
{
    private $src;
    private $image;
    private $imageinfo;
    private $percent;

    /**
     * 图片压缩
     * @param string $src 源图
     * @param float $percent 压缩比例
     */
    public function __construct($src, $percent = 1)
    {
        $this->src = $src;
        $this->percent = $percent;
    }

    /** 高清压缩图片
     * @param string $saveName 提供图片名（可不带扩展名，用源图扩展名）用于保存。或不提供文件名直接显示
     */
    public function compressImg($saveName = '')
    {
        if (!$this->_openImage()) {   //打开图片
            return;
        }
        if (!empty($saveName)) $this->_saveImage($saveName);  //保存
        else $this->_showImage();
    }

    /**
     * 内部：打开图片
     */
    private function _openImage()
    {
        list($width, $height, $type, $attr) = getimagesize($this->src);
        $this->imageinfo = array(
            'width' => $width,
            'height' => $height,
            'type' => image_type_to_extension($type, false),
            'attr' => $attr
        );
        $fun = "imagecreatefrom" . $this->imageinfo['type'];
        if (!function_exists($fun)) {
            return false;
        }
        $this->image = $fun($this->src);
        $this->_thumpImage();
        return true;
    }

    /**
     * 内部：操作图片
     */
    private function _thumpImage()
    {
        $new_width = $this->imageinfo['width'] * $this->percent;
        $new_height = $this->imageinfo['height'] * $this->percent;
        $image_thump = imagecreatetruecolor($new_width, $new_height);
        //将原图复制带图片载体上面，并且按照一定比例压缩,极大的保持了清晰度
        imagecopyresampled($image_thump, $this->image, 0, 0, 0, 0, $new_width, $new_height, $this->imageinfo['width'], $this->imageinfo['height']);
        imagedestroy($this->image);
        $this->image = $image_thump;
    }

    /**
     * 输出图片:保存图片则用saveImage()
     */
    private function _showImage()
    {
        header('Content-Type: image/' . $this->imageinfo['type']);
        $funcs = "image" . $this->imageinfo['type'];
        $funcs($this->image);
    }

    /**
     * 保存图片到硬盘：
     * @param string $dstImgName 1、可指定字符串不带后缀的名称，使用源图扩展名 。2、直接指定目标图片名带扩展名。
     */
    private function _saveImage($dstImgName)
    {
        if (empty($dstImgName)) return false;
        if (str_contains($dstImgName, '.')) {
            $dstName = $dstImgName;
        } else {
            $allowImgs = ['.jpg', '.jpeg', '.webp', '.png', '.bmp', '.wbmp', '.gif'];   //如果目标图片名有后缀就用目标图片扩展名 后缀，如果没有，则用源图的扩展名
            $dstExt = strrchr($dstImgName, ".");
            $sourseExt = strrchr($this->src, ".");
            if (!empty($dstExt)) $dstExt = strtolower($dstExt);
            if (!empty($sourseExt)) $sourseExt = strtolower($sourseExt);
            //有指定目标名扩展名
            if (!empty($dstExt) && in_array($dstExt, $allowImgs)) {
                $dstName = $dstImgName;
            } elseif (!empty($sourseExt) && in_array($sourseExt, $allowImgs)) {
                $dstName = $dstImgName . $sourseExt;
            } else {
                $dstName = $dstImgName . $this->imageinfo['type'];
            }
        }
        $funcs = "image" . $this->imageinfo['type'];
        if (!function_exists($funcs)) {
            return false;
        }
        $funcs($this->image, $dstName);
        return true;
    }

    /**
     * 销毁图片
     */
    public function __destruct()
    {
        if ($this->image) {
            imagedestroy($this->image);
        }
    }

    /**
     * 压缩图片静态方法
     * @param string $src 原图地址
     * @param float $percent 压缩比例
     * @param float $minSize 最小压缩大小，小于这个不压缩，单位KB
     * @return void
     */
    public static function compress(string $src, float $percent = 1, float $minSize = 10): void
    {
        if (Base::settingFind("system", "image_compress") === 'close') {
            return;
        }
        if (!file_exists($src)) {
            return;
        }
        $allowImgs = ['.jpg', '.jpeg', '.webp', '.png', '.bmp', '.wbmp'];
        if (!in_array(strrchr($src, "."), $allowImgs)) {
            return;
        }
        $size = filesize($src);
        if ($minSize > 0 && $size < $minSize * 1024) {
            return;
        }
        try {
            $img = new ImgCompress($src, $percent);
            $tmp = $src . '.compress.tmp';
            $img->compressImg($tmp);
            if (file_exists($tmp)) {
                if (filesize($tmp) > $size) {
                    unlink($tmp);
                    return;
                }
                unlink($src);
                rename($tmp, $src);
            }
        } catch (\Exception) {
            return;
        }
    }
}
