<?php

namespace App\Module;

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;
use Laravolt\Avatar\Avatar;

/**
 * laravolt/avatar 6.5.0 的 buildAvatar 给纵向对齐传 'middle'，
 * 而 intervention/image 4.1.3 的 Alignment 枚举仅接受 'center'，会抛
 * InvalidArgumentException(Invalid value for alignment)。上游修复前以子类覆写修正。
 */
class PatchedAvatar extends Avatar
{
    public function buildAvatar(): static
    {
        $this->buildInitial();

        $x = $this->width / 2;
        $y = $this->height / 2;

        $driver = $this->driver === 'gd' ? new GdDriver : new ImagickDriver;
        $this->image = ImageManager::usingDriver($driver)->createImage($this->width, $this->height);

        $this->createShape();

        if (empty($this->initials)) {
            return $this;
        }

        $this->image->text(
            $this->initials,
            (int) $x,
            (int) $y,
            function (FontFactory $font) {
                $font->filepath($this->font);
                $font->size($this->fontSize);
                $font->color($this->foreground);
                $font->align('center', 'center');
            }
        );

        return $this;
    }
}
