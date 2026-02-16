<?php

namespace Modules\Media\Services\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * Media Upload Failed Handler
 *
 * Returns signal data when a media upload fails.
 * Notifies the user about the upload failure.
 */
class MediaUploadFailedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['media.upload.failed'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Media';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'MediaUploadFailedHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['user'])
            && $data['user'] instanceof User
            && isset($data['error']);
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        $error = $data['error'] ?? 'Unknown error';
        $filename = $data['filename'] ?? 'file';

        return [
            'type' => 'warning',
            'title' => 'Media Upload Failed',
            'message' => "Failed to upload \"{$filename}\": {$error}",
            'url' => route('media.index'),
            'category' => 'media',
        ];
    }
}
