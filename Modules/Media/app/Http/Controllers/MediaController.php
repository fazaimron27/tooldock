<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\MediaUploader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Media\Http\Requests\UploadMediaRequest;
use Modules\Media\Models\MediaFile;

class MediaController extends Controller
{
    public function __construct(
        private MediaUploader $uploader
    ) {}

    /**
     * Display a listing of media files.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', MediaFile::class);

        $mediaFiles = MediaFile::permanent()
            ->with('model')
            ->latest()
            ->paginate(20);

        return Inertia::render('Media/Index', [
            'mediaFiles' => $mediaFiles,
        ]);
    }

    /**
     * Upload a temporary file.
     */
    public function uploadTemporary(UploadMediaRequest $request): JsonResponse
    {
        $this->authorize('create', MediaFile::class);

        try {
            $file = $request->file('file');
            $directory = $request->input('directory', 'temp');

            $mediaFile = $this->uploader->upload(
                $file,
                $directory,
                null,
                isTemporary: true
            );

            return response()->json([
                'id' => $mediaFile->id,
                'path' => $mediaFile->path,
                'url' => $mediaFile->url,
                'filename' => $mediaFile->filename,
                'mime_type' => $mediaFile->mime_type,
                'size' => $mediaFile->size,
            ]);
        } catch (\Exception $e) {
            Log::error('Media upload failed (temporary)', [
                'user_id' => $request->user()?->id,
                'directory' => $request->input('directory', 'temp'),
                'filename' => $request->file('file')?->getClientOriginalName(),
                'file_size' => $request->file('file')?->getSize(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Server Error',
                'message' => app()->environment('production')
                    ? 'Failed to upload file. Please try again.'
                    : 'Failed to upload file: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload a permanent file.
     */
    public function upload(UploadMediaRequest $request): JsonResponse
    {
        $this->authorize('create', MediaFile::class);

        try {
            $file = $request->file('file');
            $directory = $request->input('directory', 'uploads');
            $modelType = $request->input('model_type');
            $modelId = $request->input('model_id');

            $model = null;
            if ($modelType && $modelId) {
                if (! class_exists($modelType) || ! is_subclass_of($modelType, Model::class)) {
                    Log::warning('Invalid model type provided for media upload', [
                        'user_id' => $request->user()?->id,
                        'model_type' => $modelType,
                        'model_id' => $modelId,
                        'class_exists' => class_exists($modelType),
                        'is_model_subclass' => class_exists($modelType) && is_subclass_of($modelType, Model::class),
                    ]);

                    return response()->json([
                        'error' => 'Validation Failed',
                        'message' => 'Invalid model type provided.',
                    ], 422);
                }

                $model = $modelType::find($modelId);

                if (! $model) {
                    Log::warning('Model not found for media upload', [
                        'user_id' => $request->user()?->id,
                        'model_type' => $modelType,
                        'model_id' => $modelId,
                    ]);

                    return response()->json([
                        'error' => 'Not Found',
                        'message' => 'Model not found.',
                    ], 404);
                }
            }

            $mediaFile = $this->uploader->upload(
                $file,
                $directory,
                $model,
                isTemporary: false
            );

            return response()->json([
                'id' => $mediaFile->id,
                'path' => $mediaFile->path,
                'url' => $mediaFile->url,
                'filename' => $mediaFile->filename,
                'mime_type' => $mediaFile->mime_type,
                'size' => $mediaFile->size,
            ]);
        } catch (\Exception $e) {
            Log::error('Media upload failed (permanent)', [
                'user_id' => $request->user()?->id,
                'directory' => $request->input('directory', 'uploads'),
                'filename' => $request->file('file')?->getClientOriginalName(),
                'file_size' => $request->file('file')?->getSize(),
                'model_type' => $request->input('model_type'),
                'model_id' => $request->input('model_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Server Error',
                'message' => app()->environment('production')
                    ? 'Failed to upload file. Please try again.'
                    : 'Failed to upload file: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified media file.
     */
    public function destroy(MediaFile $medium): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $medium);

        $medium->delete();

        return redirect()->route('media.index')->with('success', 'Media file deleted successfully.');
    }
}
