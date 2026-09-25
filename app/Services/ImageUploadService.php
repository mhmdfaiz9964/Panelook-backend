<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Process, compress, convert to WebP, and store an uploaded image.
     * Includes memory safety, fallback storage, and absolute URL generation.
     *
     * @param UploadedFile $file
     * @param string $folder e.g. 'products', 'banners'
     * @param int $maxWidth Max allowed width
     * @param int $maxHeight Max allowed height
     * @param int $quality WebP quality (1-100)
     * @return array
     */
    public function processAndStore(
        UploadedFile $file,
        string $folder = 'products',
        int $maxWidth = 1200,
        int $maxHeight = 1200,
        int $quality = 84
    ): array {
        // Boost memory limit to handle high-resolution camera photos / large PNGs
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '180');

        $originalName = $file->getClientOriginalName();
        $originalSize = $file->getSize();
        $tempPath = $file->getRealPath();

        $storageDir = "public/{$folder}";
        $fullStoragePath = storage_path("app/{$storageDir}");

        if (!file_exists($fullStoragePath)) {
            @mkdir($fullStoragePath, 0755, true);
        }

        $randomSlug = Str::random(20);

        // Attempt WebP conversion via GD
        $srcImage = null;
        $dstImage = null;

        try {
            $imageInfo = @getimagesize($tempPath);
            if (!$imageInfo) {
                return $this->storeDirectFallback($file, $fullStoragePath, $folder, $randomSlug, $originalName, $originalSize);
            }

            [$width, $height, $imageType] = $imageInfo;

            // Load image resource based on type
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $srcImage = @imagecreatefromjpeg($tempPath);
                    break;
                case IMAGETYPE_PNG:
                    $srcImage = @imagecreatefrompng($tempPath);
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $srcImage = @imagecreatefromwebp($tempPath);
                    }
                    break;
                default:
                    // If unsupported by GD, store directly
                    return $this->storeDirectFallback($file, $fullStoragePath, $folder, $randomSlug, $originalName, $originalSize);
            }

            if (!$srcImage) {
                return $this->storeDirectFallback($file, $fullStoragePath, $folder, $randomSlug, $originalName, $originalSize);
            }

            // Calculate target dimensions maintaining aspect ratio
            $targetWidth = $width;
            $targetHeight = $height;

            if ($width > $maxWidth || $height > $maxHeight) {
                $ratio = min($maxWidth / $width, $maxHeight / $height);
                $targetWidth = (int) round($width * $ratio);
                $targetHeight = (int) round($height * $ratio);
            }

            // Create destination canvas
            $dstImage = imagecreatetruecolor($targetWidth, $targetHeight);

            // Preserve alpha transparency
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
            imagefilledrectangle($dstImage, 0, 0, $targetWidth, $targetHeight, $transparent);
            imagealphablending($dstImage, true);

            // Resample
            imagecopyresampled(
                $dstImage,
                $srcImage,
                0, 0, 0, 0,
                $targetWidth,
                $targetHeight,
                $width,
                $height
            );

            $filename = "{$randomSlug}.webp";
            $targetFile = "{$fullStoragePath}/{$filename}";

            // Save as WebP
            $saved = false;
            if (function_exists('imagewebp')) {
                $saved = @imagewebp($dstImage, $targetFile, $quality);
            }

            if (!$saved) {
                $filename = "{$randomSlug}.jpg";
                $targetFile = "{$fullStoragePath}/{$filename}";
                @imagejpeg($dstImage, $targetFile, $quality);
            }

            $finalSize = file_exists($targetFile) ? filesize($targetFile) : $originalSize;
            $publicUrl = asset("storage/{$folder}/{$filename}");

            return [
                'success' => true,
                'url' => $publicUrl,
                'filename' => $filename,
                'original_name' => $originalName,
                'original_size' => $originalSize,
                'final_size' => $finalSize,
                'width' => $targetWidth,
                'height' => $targetHeight,
                'format' => str_ends_with($filename, '.webp') ? 'webp' : 'jpg',
            ];
        } catch (\Throwable $ex) {
            Log::warning("GD image compression failed, falling back to direct storage: " . $ex->getMessage());
            return $this->storeDirectFallback($file, $fullStoragePath, $folder, $randomSlug, $originalName, $originalSize);
        } finally {
            if ($srcImage && is_resource($srcImage)) {
                @imagedestroy($srcImage);
            }
            if ($dstImage && is_resource($dstImage)) {
                @imagedestroy($dstImage);
            }
        }
    }

    /**
     * Fallback to store file directly if GD conversion fails or is unsupported.
     */
    protected function storeDirectFallback(
        UploadedFile $file,
        string $fullStoragePath,
        string $folder,
        string $randomSlug,
        string $originalName,
        int $originalSize
    ): array {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = "{$randomSlug}.{$extension}";
        $targetFile = "{$fullStoragePath}/{$filename}";

        $file->move($fullStoragePath, $filename);

        $finalSize = file_exists($targetFile) ? filesize($targetFile) : $originalSize;
        $publicUrl = asset("storage/{$folder}/{$filename}");

        return [
            'success' => true,
            'url' => $publicUrl,
            'filename' => $filename,
            'original_name' => $originalName,
            'original_size' => $originalSize,
            'final_size' => $finalSize,
            'width' => 1200,
            'height' => 1200,
            'format' => $extension,
        ];
    }
}
