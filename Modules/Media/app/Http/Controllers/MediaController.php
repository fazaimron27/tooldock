<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Media\MediaUploader;
use App\Services\Registry\SignalHandlerRegistry;
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
        private MediaUploader $uploader,
        private readonly SignalHandlerRegistry $signalRegistry
    ) {}

    /**
     * Display a listing of media files.
     *
     * Filters by parent model ownership:
     * - Super Admins see all files
     * - Regular users only see files attached to their own models
     */
    public function index(): Response
    {
        $this->authorize('viewAny', MediaFile::class);

        $user = request()->user();
        $query = MediaFile::permanent()->with('model')->latest();

        // Super Admin sees all, others see only their owned files
        if (! $user->hasRole(\Modules\Core\Constants\Roles::SUPER_ADMIN)) {
            $userId = $user->id;
            $userClass = \Modules\Core\Models\User::class;

            $query->where(function ($q) use ($userId, $userClass) {
                // Files attached directly to the user (e.g., avatar)
                $q->where(function ($sub) use ($userId, $userClass) {
                    $sub->where('model_type', $userClass)
                        ->where('model_id', $userId);
                });

                // Files attached to models owned by the user
                // We need to check each model type that has user_id
                $q->orWhereHas('model', function ($modelQuery) use ($userId) {
                    // This works for models that have user_id column
                    $modelQuery->where('user_id', $userId);
                });
            });
        }

        $mediaFiles = $query->paginate(20);

        return Inertia::render('Modules::Media/Index', [
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
            $user = $request->user();
            $filename = $request->file('file')?->getClientOriginalName() ?? 'Unknown';

            Log::error('Media upload failed (temporary)', [
                'user_id' => $user?->id,
                'directory' => $request->input('directory', 'temp'),
                'filename' => $filename,
                'file_size' => $request->file('file')?->getSize(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($user) {
                $this->signalRegistry->dispatch('media.upload.failed', [
                    'user' => $user,
                    'filename' => $filename,
                    'error' => $e->getMessage(),
                ]);
            }

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
            $user = $request->user();
            $filename = $request->file('file')?->getClientOriginalName() ?? 'Unknown';

            Log::error('Media upload failed (permanent)', [
                'user_id' => $user?->id,
                'directory' => $request->input('directory', 'uploads'),
                'filename' => $filename,
                'file_size' => $request->file('file')?->getSize(),
                'model_type' => $request->input('model_type'),
                'model_id' => $request->input('model_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($user) {
                $this->signalRegistry->dispatch('media.upload.failed', [
                    'user' => $user,
                    'filename' => $filename,
                    'error' => $e->getMessage(),
                ]);
            }

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
