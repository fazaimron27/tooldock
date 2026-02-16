<?php

/**
 * Media Uploader.
 *
 * Handles file uploads with automatic image optimization, WebP conversion,
 * and dimension scaling. Stores files on the configured disk (local or S3),
 * creates MediaFile database records, and provides transactional safety
 * with automatic cleanup on failure.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace App\Services\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Modules\Media\Models\MediaFile;

/**
 * File upload handler with image optimization support.
 *
 * Uploads files to the configured storage disk, optionally optimizes images
 * (scaling, quality adjustment, WebP conversion), and creates corresponding
 * MediaFile records. All operations are wrapped in database transactions
 * with automatic file cleanup on failure.
 *
 * @see MediaFile The model representing uploaded media files
 * @see MediaConfigService Provides file size configuration
 */
class MediaUploader
{
    /**
     * Create a new media uploader instance.
     *
     * Initializes the image manager with the GD driver if the extension
     * is available, enabling automatic image optimization during uploads.
     *
     * @param  ImageManager|null  $imageManager  Optional image manager instance (auto-created if GD available)
     */
    public function __construct(
        private ?ImageManager $imageManager = null
    ) {
        if (extension_loaded('gd')) {
            $this->imageManager = new ImageManager(new Driver);
        }
    }

    /**
     * Upload a file and create a media record.
     *
     * Stores the file on the configured disk, optimizes images if GD is available
     * and the disk is local, then creates a MediaFile database record. All operations
     * are wrapped in a transaction with automatic file cleanup on failure.
     *
     * @param  UploadedFile  $file  The uploaded file instance
     * @param  string  $directory  Storage directory path
     * @param  Model|null  $model  Optional parent model to associate the file with
     * @param  bool  $isTemporary  Whether the file is temporary (e.g., pending attachment)
     * @return MediaFile The created media file record
     *
     * @throws \RuntimeException When file storage or verification fails
     * @throws \Exception When any other error occurs during upload
     */
    public function upload(
        UploadedFile $file,
        string $directory = 'uploads',
        ?Model $model = null,
        bool $isTemporary = false
    ): MediaFile {
        $disk = $this->getDisk();
        $filename = $file->hashName();
        $storedPath = null;
        $mediaFile = null;

        try {
            DB::beginTransaction();

            $storedPath = $file->storeAs($directory, $filename, $disk);

            if (! $storedPath) {
                throw new \RuntimeException('Failed to store file on disk.');
            }

            if (! Storage::disk($disk)->exists($storedPath)) {
                throw new \RuntimeException('File storage verification failed.');
            }

            $fileSize = Storage::disk($disk)->size($storedPath);
            $mimeType = $file->getMimeType();

            if ($this->isImage($file) && $this->imageManager && $this->isLocalDisk($disk)) {
                $optimizationResult = $this->optimizeImage($disk, $storedPath, $mimeType);
                if ($optimizationResult) {
                    if (isset($optimizationResult['path']) && $optimizationResult['path'] !== $storedPath) {
                        $storedPath = $optimizationResult['path'];
                    }
                    $fileSize = Storage::disk($disk)->size($storedPath);
                    $mimeType = $optimizationResult['mime_type'] ?? $mimeType;
                }
            }
            $mediaFile = MediaFile::create([
                'disk' => $disk,
                'path' => $storedPath,
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $mimeType,
                'size' => $fileSize,
                'model_type' => $model ? $model::class : null,
                'model_id' => $model?->id,
                'is_temporary' => $isTemporary,
            ]);

            DB::commit();

            return $mediaFile;
        } catch (\Exception $e) {
            DB::rollBack();

            if ($storedPath && Storage::disk($disk)->exists($storedPath)) {
                try {
                    Storage::disk($disk)->delete($storedPath);
                } catch (\Exception $deleteException) {
                    Log::error('Failed to delete file after upload error', [
                        'path' => $storedPath,
                        'error' => $deleteException->getMessage(),
                    ]);
                }
            }

            Log::error('Media upload failed', [
                'directory' => $directory,
                'filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the configured storage disk.
     *
     * @return string The disk name ('public', 'local', or 's3')
     */
    private function getDisk(): string
    {
        $defaultDisk = config('filesystems.default');
        $mediaDisk = settings('default_storage_disk', 'public');

        return in_array($mediaDisk, ['public', 'local', 's3']) ? $mediaDisk : 'public';
    }

    /**
     * Check if file is an image.
     *
     * @param  UploadedFile  $file  The uploaded file to check
     * @return bool True if the file is a raster image (excludes SVG)
     */
    private function isImage(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();

        return str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml';
    }

    /**
     * Check if disk supports local file access.
     *
     * @param  string  $disk  The disk name to check
     * @return bool True if the disk uses the local driver
     */
    private function isLocalDisk(string $disk): bool
    {
        $diskConfig = config("filesystems.disks.{$disk}");

        return isset($diskConfig['driver']) && $diskConfig['driver'] === 'local';
    }

    /**
     * Optimize an image file.
     *
     * Scales down oversized images, adjusts quality, and optionally converts
     * to WebP format. Replaces the original file if format conversion occurs.
     *
     * @param  string  $disk  The storage disk name
     * @param  string  $path  The file path on disk
     * @param  string  $originalMimeType  The original MIME type of the image
     * @return array{path: string, mime_type: string}|null Optimization result, or null on failure
     */
    private function optimizeImage(string $disk, string $path, string $originalMimeType): ?array
    {
        if (! $this->imageManager) {
            return null;
        }

        try {
            $fullPath = Storage::disk($disk)->path($path);
            $image = $this->imageManager->read($fullPath);

            $maxDimension = (int) settings('image_max_dimension', 2000);
            if ($image->width() > $maxDimension || $image->height() > $maxDimension) {
                $image->scaleDown($maxDimension, $maxDimension);
            }

            $preferWebP = (bool) settings('prefer_webp', false);
            $quality = (int) settings('image_quality', 85);

            $formatMap = [
                'image/jpeg' => 'jpeg',
                'image/jpg' => 'jpeg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
            ];

            $originalFormat = $formatMap[$originalMimeType] ?? 'jpeg';

            $outputFormat = ($preferWebP && function_exists('imagewebp')) ? 'webp' : $originalFormat;
            $encoded = match ($outputFormat) {
                'webp' => $image->toWebp($quality),
                'png' => $image->toPng(),
                'gif' => $image->toGif(),
                default => $image->toJpeg($quality),
            };

            $newPath = $path;
            if ($outputFormat !== $originalFormat) {
                $pathInfo = pathinfo($path);
                $dirname = ($pathInfo['dirname'] !== '.' && $pathInfo['dirname'] !== '') ? $pathInfo['dirname'] : '';
                $newPath = $dirname ? $dirname.'/'.$pathInfo['filename'].'.'.$outputFormat : $pathInfo['filename'].'.'.$outputFormat;
            }

            $savePath = Storage::disk($disk)->path($newPath);
            $encoded->save($savePath);

            if ($newPath !== $path && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }

            return [
                'path' => $newPath,
                'mime_type' => match ($outputFormat) {
                    'webp' => 'image/webp',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    default => 'image/jpeg',
                },
            ];
        } catch (\Exception $e) {
            Log::warning('Image optimization failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
