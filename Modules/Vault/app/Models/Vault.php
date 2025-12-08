<?php

namespace Modules\Vault\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Modules\AuditLog\Traits\LogsActivity;
use Modules\Categories\Models\Category;
use Modules\Core\Models\User;
use OTPHP\TOTP;
use Symfony\Component\Clock\Clock;

class Vault extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    /**
     * Available vault types.
     *
     * @var array<string>
     */
    public const TYPES = ['login', 'card', 'note', 'server'];

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
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'type',
        'name',
        'username',
        'email',
        'issuer',
        'value',
        'totp_secret',
        'fields',
        'url',
        'is_favorite',
    ];

    /**
     * Fields to exclude from audit logging.
     * Sensitive encrypted fields should not be logged.
     */
    protected $auditExclude = ['value', 'totp_secret', 'fields'];

    /**
     * Note: totp_secret is encrypted at rest and decrypted only on the server.
     * TOTP codes are generated server-side via the generateTotp() endpoint to ensure
     * the secret is never exposed to the frontend, maintaining security best practices.
     */

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'encrypted',
            'totp_secret' => 'encrypted',
            'fields' => 'encrypted:array',
            'is_favorite' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the vault.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category associated with the vault.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope a query to only include vaults for the authenticated user.
     */
    public function scopeForUser(Builder $query): Builder
    {
        return $query->where('user_id', Auth::id());
    }

    /**
     * Get the favicon URL attribute.
     *
     * Returns the DuckDuckGo favicon service URL for the vault's domain.
     */
    public function getFaviconUrlAttribute(): ?string
    {
        if (! $this->url) {
            return null;
        }

        $domain = parse_url($this->url, PHP_URL_HOST);

        if (! $domain) {
            return null;
        }

        return "https://icons.duckduckgo.com/ip3/{$domain}.ico";
    }

    /**
     * Generate the current TOTP code for this vault.
     *
     * Uses the spomky-labs/otphp library to generate a TOTP code
     * from the decrypted totp_secret value. Code length and period
     * are configurable via settings.
     *
     * @return string|null The TOTP code, or null if no secret is set
     */
    public function generateCurrentTotpCode(): ?string
    {
        if (! $this->totp_secret) {
            return null;
        }

        try {
            $secret = $this->totp_secret;
            $clock = Clock::get();
            $codeLength = (int) settings('vault_totp_code_length', 6);
            $period = (int) settings('vault_totp_period', 30);

            $totp = TOTP::create(
                secret: $secret,
                period: $period,
                digest: 'sha1',
                digits: $codeLength,
                clock: $clock
            );

            return $totp->now();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get audit tags for this vault.
     *
     * Returns tags based on the vault's type for better filtering.
     *
     * @return array<string>
     */
    public function getAuditTags(): array
    {
        $tags = ['vault'];

        if ($this->type) {
            $tags[] = strtolower($this->type);
        }

        if ($this->category_id) {
            $tags[] = 'categorized';
        }

        return $tags;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return \Modules\Vault\Database\Factories\VaultFactory::new();
    }
}
