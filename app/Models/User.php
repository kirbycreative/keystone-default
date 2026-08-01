<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Keystone\Admin\Contracts\ManagesKeystoneSite;
use Keystone\Admin\Models\ContentAsset;
use Keystone\Admin\Models\Onboarding;
use Keystone\Admin\Models\PageSuggestion;
use Keystone\Admin\Services\KeystoneApiService;
use Keystone\Admin\Traits\HasClientAssets;

class User extends Authenticatable implements ManagesKeystoneSite
{
    public const ROLE_OWNER = 'owner';

    public const ROLE_EDITOR = 'editor';

    public const ROLE_VIEWER = 'viewer';

    /** @use HasFactory<UserFactory> */
    use HasClientAssets, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'onboarded',
        'role',
        'mfa_secret',
        'mfa_confirmed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'onboarded' => 'boolean',
            'mfa_secret' => 'encrypted',
            'mfa_confirmed_at' => 'datetime',
        ];
    }

    public function contentAssets(): HasMany
    {
        return $this->hasMany(ContentAsset::class);
    }

    public function pageSuggestions(): HasMany
    {
        return $this->hasMany(PageSuggestion::class);
    }

    public function onboarding(): HasOne
    {
        return $this->hasOne(Onboarding::class);
    }

    /**
     * The user's onboarding record, created on first access so callers always get a usable model.
     */
    public function onboardingState(): Onboarding
    {
        return $this->onboarding()->firstOrCreate([], ['step' => Onboarding::STEP_DNS]);
    }

    public function canEditSite(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_EDITOR], true);
    }

    public function canPublishSite(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function canAccessKeystoneAdmin(): bool
    {
        return true;
    }

    public function canEditKeystoneSite(): bool
    {
        return $this->canEditSite();
    }

    public function canPublishKeystoneSite(): bool
    {
        return $this->canPublishSite();
    }

    public function sendPasswordResetNotification($token): void
    {
        app(KeystoneApiService::class)->sendPasswordReset(
            $this->getEmailForPasswordReset(),
            route('password.reset', [
                'token' => $token,
                'email' => $this->getEmailForPasswordReset(),
            ]),
        );
    }
}
