<?php

namespace App\Services\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Modules\Media\Models\MediaFile;

class MediaUploader
{
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
     * @param  UploadedFile  $file
     * @param  string  $directory
     * @param  Model|null  $model
     * @param  bool  $isTemporary
     * @return MediaFile
     *
     * @throws \Exception
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
     * Get the configured disk.
     */
    private function getDisk(): string
    {
        $defaultDisk = config('filesystems.default');
        $mediaDisk = settings('default_storage_disk', 'public');

        return in_array($mediaDisk, ['public', 'local', 's3']) ? $mediaDisk : 'public';
    }

    /**
     * Check if file is an image.
     */
    private function isImage(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();

        return str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml';
    }

    /**
     * Check if disk supports local file access.
     */
    private function isLocalDisk(string $disk): bool
    {
        $diskConfig = config("filesystems.disks.{$disk}");

        return isset($diskConfig['driver']) && $diskConfig['driver'] === 'local';
    }

    /**
     * Optimize an image file.
     *
     * @param  string  $disk
     * @param  string  $path
     * @param  string  $originalMimeType
     * @return array{path: string, mime_type: string}|null
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
