<?php

/**
 * Streak Milestone Handler
 *
 * Notifies users when they reach a streak milestone on a habit.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Services\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * Class StreakMilestoneHandler
 *
 * Returns signal data when a user reaches a notable streak milestone
 * (e.g., 7, 14, 21, 30, 50, 100 days). Encourages habit consistency
 * through positive reinforcement notifications.
 */
class StreakMilestoneHandler implements SignalHandlerInterface
{
    /**
     * Streak milestones that trigger a notification.
     *
     * @var array<int>
     */
    private const MILESTONES = [7, 14, 21, 30, 50, 75, 100, 150, 200, 365];

    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['routine.streak.updated'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Routine';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'StreakMilestoneHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['user'])
            && $data['user'] instanceof User
            && isset($data['streak'])
            && isset($data['habit_name']);
    }

    /**
     * Milestone messages grouped by tier for varied, contextual copy.
     *
     * @var array<string, array{title: string, message: string}>
     */
    private const MILESTONE_MESSAGES = [
        7 => [
            'title' => 'One Week Strong',
            'message' => 'You\'ve completed a full week of "%s". A solid foundation for lasting change.',
        ],
        14 => [
            'title' => 'Two-Week Milestone',
            'message' => 'Two consecutive weeks of "%s" — you\'re building real momentum.',
        ],
        21 => [
            'title' => 'Three Weeks In',
            'message' => 'They say it takes 21 days to form a habit. "%s" is becoming second nature.',
        ],
        30 => [
            'title' => '30-Day Streak',
            'message' => 'A full month of "%s" without missing a beat. That takes real dedication.',
        ],
        50 => [
            'title' => '50 Days Running',
            'message' => '50 consecutive days of "%s". You\'re well past the point of habit formation.',
        ],
        75 => [
            'title' => '75-Day Milestone',
            'message' => '75 days of consistent "%s". This level of discipline is rare — own it.',
        ],
        100 => [
            'title' => 'Triple Digits',
            'message' => '100 days of "%s". A century of consistency — that\'s an achievement worth recognizing.',
        ],
        150 => [
            'title' => '150-Day Streak',
            'message' => '150 days of "%s". Half a year of unwavering commitment is within reach.',
        ],
        200 => [
            'title' => '200 Days Strong',
            'message' => '200 consecutive days of "%s". This isn\'t a streak anymore — it\'s who you are.',
        ],
        365 => [
            'title' => 'One Full Year',
            'message' => '365 days of "%s". A full year of daily commitment. Remarkable.',
        ],
    ];

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        $streak = (int) $data['streak'];
        $habitName = $data['habit_name'];

        if (! in_array($streak, self::MILESTONES, true)) {
            return null;
        }

        $template = self::MILESTONE_MESSAGES[$streak];

        return [
            'type' => 'success',
            'title' => $template['title'],
            'message' => sprintf($template['message'], $habitName),
            'url' => route('routine.index'),
            'category' => 'routine',
        ];
    }
}
