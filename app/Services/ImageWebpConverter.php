<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageWebpConverter
{
    /**
     * Converts an uploaded image (JPEG, PNG, WebP, GIF, etc.) into a compressed WebP image
     * and stores it on the specified storage disk.
     *
     * @param TemporaryUploadedFile|UploadedFile|string $file
     * @param string $directory Storage subdirectory (e.g. 'car-gallery')
     * @param string $disk Storage disk (default: 'public')
     * @param int $quality WebP compression quality (0-100, default: 82)
     * @param int $maxWidth Max width in pixels (resizes proportionally if exceeded)
     * @return string Relative storage path (e.g. 'car-gallery/01JH...webp')
     */
    public static function convertAndStore(
        TemporaryUploadedFile|UploadedFile|string $file,
        string $directory = 'car-gallery',
        string $disk = 'public',
        int $quality = 82,
        int $maxWidth = 2048
    ): string {
        $realPath = null;

        if ($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile) {
            $realPath = $file->getRealPath();
        } elseif (is_string($file)) {
            if (file_exists($file)) {
                $realPath = $file;
            } elseif (Storage::disk($disk)->exists($file)) {
                $realPath = Storage::disk($disk)->path($file);
            }
        }

        if (!$realPath || !file_exists($realPath)) {
            return is_string($file) ? $file : '';
        }

        $binary = @file_get_contents($realPath);
        if ($binary === false) {
            return is_string($file) ? $file : '';
        }

        // Create GD image resource from binary contents
        $srcImage = @imagecreatefromstring($binary);
        if ($srcImage === false) {
            // If GD cannot parse the image, store as-is
            $filename = Str::ulid() . '.' . pathinfo($realPath, PATHINFO_EXTENSION);
            $destination = trim($directory, '/') . '/' . $filename;
            Storage::disk($disk)->put($destination, $binary);
            return $destination;
        }

        $origWidth = imagesx($srcImage);
        $origHeight = imagesy($srcImage);

        // Proportional resize if larger than $maxWidth
        if ($origWidth > $maxWidth && $maxWidth > 0) {
            $newWidth = $maxWidth;
            $newHeight = (int) round(($origHeight / $origWidth) * $newWidth);

            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Handle transparency for alpha-capable formats
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
            imagefill($resizedImage, 0, 0, $transparent);

            imagecopyresampled(
                $resizedImage,
                $srcImage,
                0, 0, 0, 0,
                $newWidth,
                $newHeight,
                $origWidth,
                $origHeight
            );

            imagedestroy($srcImage);
            $srcImage = $resizedImage;
        } else {
            // Ensure alpha channel is preserved on original GD resource
            imagealphablending($srcImage, false);
            imagesavealpha($srcImage, true);
        }

        // Output to WebP in memory
        ob_start();
        imagewebp($srcImage, null, $quality);
        $webpContent = ob_get_clean();
        imagedestroy($srcImage);

        // Store compressed WebP to disk
        $filename = Str::ulid() . '.webp';
        $destination = trim($directory, '/') . '/' . $filename;
        Storage::disk($disk)->put($destination, $webpContent);

        return $destination;
    }

    /**
     * Converts an array of image files or paths to WebP.
     * Skips files that are already WebP.
     *
     * @param array $files
     * @param string $directory
     * @param string $disk
     * @return array
     */
    public static function convertMultiple(
        array $files,
        string $directory = 'car-gallery',
        string $disk = 'public'
    ): array {
        $results = [];

        foreach ($files as $file) {
            if (empty($file)) {
                continue;
            }

            // If it's already an existing WebP path on disk, preserve it
            if (is_string($file) && str_ends_with(strtolower($file), '.webp') && Storage::disk($disk)->exists($file)) {
                $results[] = $file;
                continue;
            }

            $convertedPath = static::convertAndStore($file, $directory, $disk);
            if (!empty($convertedPath)) {
                $results[] = $convertedPath;
            }
        }

        return $results;
    }
}
