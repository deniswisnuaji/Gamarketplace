<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = ['role' => 'student', 'status' => 'active', 'show_phone' => false, 'show_faculty' => true];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** @return Attribute<string, string> */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn ($value) => strtolower(trim($value)));
    }

    /** @return HasOne<UserPreference, $this> */
    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    /** @return HasMany<Listing, $this> */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'seller_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'safety_agreed_at' => 'datetime',
            'show_phone' => 'boolean',
            'show_faculty' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
