<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Keystone\Toolkit\Traits\HasClientAssets;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasClientAssets;

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
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
}
