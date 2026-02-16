<?php

/**
 * Settings Changed Handler.
 *
 * Notifies administrators when application settings are modified.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Settings\Services\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * Settings Changed Handler
 *
 * Returns signal data when settings are changed.
 * Notifies administrators about the settings change.
 */
class SettingsChangedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['settings.changed'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Settings';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'SettingsChangedHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['user'])
            && $data['user'] instanceof User
            && isset($data['changed_settings']);
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        $changedCount = count($data['changed_settings']);
        $settingNames = implode(', ', array_slice($data['changed_settings'], 0, 3));
        $andMore = $changedCount > 3 ? ' and more...' : '';

        return [
            'type' => 'info',
            'title' => 'Settings Updated',
            'message' => "Settings have been updated: {$settingNames}{$andMore}",
            'url' => route('settings.index'),
            'category' => 'system',
        ];
    }
}
