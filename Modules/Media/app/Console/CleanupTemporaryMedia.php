<?php

namespace Modules\Media\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;

class CleanupTemporaryMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:cleanup-temporary {--hours=24 : Number of hours old temporary files should be before cleanup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up temporary media files older than specified hours';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        // Use setting if hours option not provided
        if ($hours === 24 && $this->option('hours') === null) {
            $hours = (int) settings('temporary_file_retention_hours', 24);
        }

        $cutoffTime = now()->subHours($hours);

        $temporaryFiles = MediaFile::temporary()
            ->where('created_at', '<', $cutoffTime)
            ->get();

        $deletedCount = 0;

        foreach ($temporaryFiles as $file) {
            try {
                // Delete from storage
                if (Storage::disk($file->disk)->exists($file->path)) {
                    Storage::disk($file->disk)->delete($file->path);
                }

                // Delete database record
                $file->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                $this->error("Failed to delete file {$file->id}: {$e->getMessage()}");
            }
        }

        $this->info("Cleaned up {$deletedCount} temporary media file(s) older than {$hours} hours.");

        return Command::SUCCESS;
    }
}
