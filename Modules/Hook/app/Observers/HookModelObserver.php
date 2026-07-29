<?php

/**
 * HookModelObserver
 *
 * A generic Eloquent model observer registered by the Hook module against
 * any model class declared in HookEventRegistry. When a lifecycle event
 * fires (created/updated/deleted), dispatches SendOutboundWebhookJob for
 * every matching active outbound webhook — without any code in target modules.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Hook\Jobs\SendOutboundWebhookJob;
use Modules\Hook\Models\HookOutbound;
use Modules\Hook\Services\HookEventRegistry;

/**
 * Class HookModelObserver
 */
class HookModelObserver
{
    /**
     * Create a new observer instance.
     */
    public function __construct(
        private readonly HookEventRegistry $registry,
    ) {}

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        $this->dispatch($model, 'created');
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->dispatch($model, 'updated');
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->dispatch($model, 'deleted');
    }

    /**
     * Find matching outbound webhooks and queue delivery jobs.
     */
    private function dispatch(Model $model, string $on): void
    {
        $key = $this->registry->findKey($model::class, $on);

        if ($key === null) {
            return;
        }

        $userId = $this->ownerId($model);

        if ($userId === null) {
            return;
        }

        $triggerDef = $this->registry->all()[$key];
        $formatter = $triggerDef['formatter'] ?? null;
        $schema = $triggerDef['payloadSchema'] ?? [];
        $modelPayload = $formatter
            ? $formatter($model)
            : $this->buildPayload($model, $schema);

        $outbounds = HookOutbound::where('trigger', $key)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        foreach ($outbounds as $outbound) {
            $template = $outbound->payload_template ?? [];
            $rendered = $this->renderTemplate($template, $modelPayload);

            $payload = array_merge(
                ['_trigger' => $key, '_data' => $modelPayload],
                $rendered,
            );

            SendOutboundWebhookJob::dispatch(
                $outbound,
                $userId,
                null,
                $payload,
                true,
            );
        }
    }

    /**
     * Resolve direct model ownership first, then the current actor for indirectly owned models.
     */
    private function ownerId(Model $model): ?string
    {
        $userId = $model->hasAttribute('user_id')
            ? $model->getAttribute('user_id')
            : Auth::id();

        if (! is_int($userId) && ! is_string($userId)) {
            return null;
        }

        $userId = (string) $userId;

        return $userId !== '' ? $userId : null;
    }

    /**
     * Recursively replace {{field}} placeholders in a template array
     * with values from the model payload.
     *
     * @param  array<string, mixed>  $template
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function renderTemplate(array $template, array $data): array
    {
        array_walk_recursive($template, function (mixed &$value) use ($data): void {
            if (! is_string($value)) {
                return;
            }

            $value = preg_replace_callback(
                '/\{\{(\w+)\}\}/',
                fn (array $m) => array_key_exists($m[1], $data)
                    ? (string) $data[$m[1]]
                    : $m[0],
                $value,
            );
        });

        return $template;
    }

    /**
     * Extract payloadSchema fields from the model when no formatter is defined.
     *
     * @param  list<string>  $schema
     * @return array<string, mixed>
     */
    private function buildPayload(Model $model, array $schema): array
    {
        $all = $model->toArray();

        if (empty($schema)) {
            return $all;
        }

        return array_intersect_key($all, array_flip($schema));
    }
}
