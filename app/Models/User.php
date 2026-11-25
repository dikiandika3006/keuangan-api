<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'sync_seq'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'premium_until' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function syncRecords(): HasMany
    {
        return $this->hasMany(SyncRecord::class);
    }

    public function isPremium(): bool
    {
        return $this->plan === 'premium' && ($this->premium_until === null || $this->premium_until->isFuture());
    }

    /** Bentuk profil yang dikirim ke aplikasi. */
    public function toProfile(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'plan' => $this->isPremium() ? 'premium' : 'free',
            'premium_until' => $this->premium_until?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
