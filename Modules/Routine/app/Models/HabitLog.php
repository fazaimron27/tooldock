<?php

/**
 * Habit Log Model
 *
 * Represents a single completion entry for a habit.
 * Stores an optional numeric value for measurable habits.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class HabitLog
 *
 * @property string $id
 * @property string $habit_id
 * @property \Illuminate\Support\Carbon $completed_at
 * @property float|null $value
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Habit $habit
 */
class HabitLog extends Model
{
    use HasFactory, HasUuids;

    /**
     * The data type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'habit_id',
        'completed_at',
        'value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'date',
            'value' => 'decimal:2',
        ];
    }

    /**
     * Get the habit that this log belongs to.
     *
     * @return BelongsTo
     */
    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }
}
