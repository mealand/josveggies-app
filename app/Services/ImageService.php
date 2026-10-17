<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Re-encodes every upload to a compressed WebP. Re-encoding strips metadata (EXIF, GPS)
 * and any payload hidden in the original file; filenames are random.
 */
class ImageService
{
    public function storeProductPhoto(UploadedFile $file, ?string $replacePath = null): string
    {
        $image = ImageManager::usingDriver(Driver::class)->decode($file->getRealPath());
        $image->scaleDown(width: 1200);

        $path = 'products/'.Str::random(32).'.webp';
        Storage::disk('public')->put($path, (string) $image->encode(new WebpEncoder(quality: 78)));

        if ($replacePath !== null) {
            Storage::disk('public')->delete($replacePath);
        }

        return $path;
    }
}
