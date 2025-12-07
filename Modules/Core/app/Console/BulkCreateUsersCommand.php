<?php

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\AuditLog\Models\AuditLog;
use Modules\Core\Models\User;

class BulkCreateUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'users:bulk-create
                            {--with-observer=25000 : Number of users to create with observer (Guest assignment)}
                            {--without-observer=25000 : Number of users to create without observer}
                            {--chunk=2000 : Chunk size for batch processing}
                            {--trigger-logs : Trigger audit logs immediately after creation}
                            {--log-chunk=1000 : Chunk size for triggering audit logs}';

    /**
     * The console command description.
     */
    protected $description = 'Bulk create users with different observer/activity logging configurations. Audit logs can be triggered manually later.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $withObserver = (int) $this->option('with-observer');
        $withoutObserver = (int) $this->option('without-observer');
        $chunkSize = (int) $this->option('chunk');
        $triggerLogs = $this->option('trigger-logs');
        $logChunkSize = (int) $this->option('log-chunk');

        /**
         * Validate command input parameters to prevent invalid operations.
         */
        if ($withObserver <= 0 && $withoutObserver <= 0) {
            $this->error('At least one of --with-observer or --without-observer must be greater than 0.');

            return Command::FAILURE;
        }

        if ($withObserver < 0 || $withoutObserver < 0) {
            $this->error('User counts cannot be negative.');

            return Command::FAILURE;
        }

        if ($chunkSize < 1 || $chunkSize > 10000) {
            $this->error('Chunk size must be between 1 and 10,000.');

            return Command::FAILURE;
        }

        if ($logChunkSize < 1 || $logChunkSize > 5000) {
            $this->error('Log chunk size must be between 1 and 5,000.');

            return Command::FAILURE;
        }

        $totalUsers = $withObserver + $withoutObserver;
        if ($totalUsers > 1000000) {
            $this->error('Total users to create cannot exceed 1,000,000. Please create in smaller batches.');

            return Command::FAILURE;
        }

        /**
         * Initialize shared timestamp and counter for email uniqueness across all batches.
         * Ensures no email collisions when creating users in multiple batches.
         */
        $baseTimestamp = now()->timestamp;
        $emailCounter = 0;

        /**
         * Batch 1: Create users with observer enabled (Guest group assignment).
         * Observer handles group assignment, but LogsActivity is disabled to prevent queued jobs.
         * Audit logs are inserted directly for better performance.
         */
        if ($withObserver > 0) {
            $ids = $this->createUsersBatch(
                count: $withObserver,
                chunkSize: $chunkSize,
                emailCounter: $emailCounter,
                baseTimestamp: $baseTimestamp,
                useObserver: true,
                description: "Creating {$withObserver} users WITH observer (Guest assignment)..."
            );

            $this->info("Created {$withObserver} users with observer (Guest group assigned).");

            /**
             * Optionally trigger audit log creation using direct bulk insertion.
             * Bypasses queue system for faster processing of large batches.
             */
            if ($triggerLogs) {
                $this->info('Triggering audit logs for batch 1 users (direct insertion)...');
                $this->triggerAuditLogs($ids, $logChunkSize);
            }
        }

        /**
         * Batch 2: Create users without observer (no automatic group assignment).
         * All events are disabled, and audit logs are inserted directly if requested.
         */
        if ($withoutObserver > 0) {
            $ids = $this->createUsersBatch(
                count: $withoutObserver,
                chunkSize: $chunkSize,
                emailCounter: $emailCounter,
                baseTimestamp: $baseTimestamp,
                useObserver: false,
                description: "Creating {$withoutObserver} users WITHOUT observer..."
            );

            $this->info("Created {$withoutObserver} users without observer (no groups assigned).");

            /**
             * Optionally trigger audit log creation using direct bulk insertion.
             * Bypasses queue system for faster processing of large batches.
             */
            if ($triggerLogs) {
                $this->info('Triggering audit logs for batch 2 users (direct insertion)...');
                $this->triggerAuditLogs($ids, $logChunkSize);
            }
        }

        $totalCreated = $withObserver + $withoutObserver;
        $this->newLine();
        $this->info("Successfully created {$totalCreated} users!");
        $this->info('Batch 1: Observer triggered (Guest assigned) + Audit logs inserted directly (no queue).');
        $this->info('Batch 2: Observer skipped (no groups) + Audit logs inserted directly (no queue).');

        return Command::SUCCESS;
    }

    /**
     * Manually trigger audit logs for created users using direct insertion.
     *
     * Uses bulk insert for better performance instead of queued jobs.
     *
     * @param  array<string>  $userIds  Array of user IDs
     */
    protected function triggerAuditLogs(array $userIds, int $chunkSize): void
    {
        $bar = $this->output->createProgressBar(count($userIds));
        $bar->start();

        $chunks = array_chunk($userIds, $chunkSize);

        foreach ($chunks as $chunk) {
            $users = User::whereIn('id', $chunk)->get();

            $auditLogEntries = [];
            foreach ($users as $user) {
                /**
                 * Filter sensitive fields before storing in audit log.
                 * Uses same filtering logic as LogsActivity trait for consistency.
                 */
                $attributes = $this->filterSensitiveFields($user->getAttributes(), $user);

                $auditLogEntries[] = [
                    'event' => 'created',
                    'model' => $user,
                    'oldValues' => null,
                    'newValues' => $attributes,
                    'userId' => null, // Console context
                    'url' => null,
                    'ipAddress' => null,
                    'userAgent' => null,
                    'tags' => 'user,bulk,console',
                ];

                $bar->advance();
            }

            /**
             * Perform bulk insert of audit logs for current chunk.
             * More efficient than individual inserts for large batches.
             */
            if (! empty($auditLogEntries)) {
                AuditLog::bulkInsert($auditLogEntries);
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Created audit logs directly for '.count($userIds).' users (no queue).');
    }

    /**
     * Create a batch of users with specified configuration.
     *
     * @param  int  $count  Number of users to create
     * @param  int  $chunkSize  Chunk size for batch processing
     * @param  int  $emailCounter  Starting email counter (passed by reference for uniqueness)
     * @param  int  $baseTimestamp  Base timestamp for unique emails
     * @param  bool  $useObserver  Whether to use observer (true) or disable events (false)
     * @param  string  $description  Description message for progress output
     * @return array<string> Array of created user IDs
     */
    protected function createUsersBatch(
        int $count,
        int $chunkSize,
        int &$emailCounter,
        int $baseTimestamp,
        bool $useObserver,
        string $description
    ): array {
        $this->info($description);
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $ids = [];

        $createCallback = function () use ($chunkSize, $count, $bar, &$ids, &$emailCounter, $baseTimestamp) {
            DB::transaction(function () use ($chunkSize, $count, $bar, &$ids, &$emailCounter, $baseTimestamp) {
                for ($i = 0; $i < $count; $i += $chunkSize) {
                    $batchSize = min($chunkSize, $count - $i);

                    /**
                     * Reset Faker's unique generator to prevent collisions across batches.
                     * Generate emails using timestamp + counter for guaranteed uniqueness.
                     */
                    fake()->unique(true);
                    $users = User::factory()->count($batchSize)->state(function (array $attributes) use (&$emailCounter, $baseTimestamp) {
                        $emailCounter++;

                        return [
                            'email' => "user_{$baseTimestamp}_{$emailCounter}_".Str::random(8).'@example.com',
                        ];
                    })->create();

                    $userIds = $users->pluck('id')->toArray();
                    $ids = array_merge($ids, $userIds);
                    $bar->advance($batchSize);
                }
            });
        };

        if ($useObserver) {
            /**
             * Execute with observer enabled but LogsActivity disabled.
             * Observer handles group assignment, but audit logs are inserted directly to avoid queued jobs.
             */
            User::withoutLoggingActivity($createCallback);
        } else {
            /**
             * Execute with all events disabled (no observer, no LogsActivity).
             * Audit logs can be inserted manually later if needed.
             */
            User::withoutEvents($createCallback);
        }

        $bar->finish();
        $this->newLine();

        return $ids;
    }

    /**
     * Filter and redact sensitive fields from attributes (aligned with LogsActivity trait).
     * Uses the same logic as the trait for consistency.
     */
    protected function filterSensitiveFields(array $attributes, User $user): array
    {
        $excludedFields = [];
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'remember_token',
            'api_token',
            'secret',
            'token',
            'credit_card',
            'credit_card_number',
            'cvv',
            'ssn',
            'social_security_number',
        ];

        // Check for model-specific exclusions
        if (property_exists($user, 'auditExclude') && is_array($user->auditExclude)) {
            $excludedFields = array_merge($excludedFields, $user->auditExclude);
        }

        // Check for model-specific sensitive fields
        if (property_exists($user, 'auditSensitive') && is_array($user->auditSensitive)) {
            $sensitiveFields = array_merge($sensitiveFields, $user->auditSensitive);
        }

        $processed = [];

        foreach ($attributes as $key => $value) {
            // Completely exclude fields
            if (in_array($key, $excludedFields, true)) {
                continue;
            }

            // Redact (mask) sensitive fields
            if (in_array($key, $sensitiveFields, true)) {
                $processed[$key] = '***REDACTED***';
            } else {
                $processed[$key] = $value;
            }
        }

        return $processed;
    }
}
