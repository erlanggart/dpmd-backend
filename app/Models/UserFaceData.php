<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class UserFaceData extends Model
{
    protected $fillable = [
        'user_id',
        'face_id',
        'encrypted_descriptor',
        'face_metadata',
        'confidence_score',
        'registration_ip',
        'registration_user_agent',
        'last_used_at',
        'usage_count',
        'is_active',
        'is_verified'
    ];

    protected $casts = [
        'face_metadata' => 'array',
        'confidence_score' => 'decimal:4',
        'last_used_at' => 'datetime',
        'usage_count' => 'integer',
        'is_active' => 'boolean',
        'is_verified' => 'boolean'
    ];

    protected $hidden = [
        'encrypted_descriptor'
    ];

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate unique face ID
     */
    public static function generateFaceId(int $userId, string $role): string
    {
        $timestamp = now()->timestamp;
        $random = bin2hex(random_bytes(8));
        $baseString = "{$role}_{$userId}_{$timestamp}_{$random}";
        return hash('sha256', $baseString);
    }

    /**
     * Encrypt face descriptor
     */
    public function setEncryptedDescriptorAttribute($value)
    {
        // If value is already encrypted (string), store as is
        if (is_string($value)) {
            $this->attributes['encrypted_descriptor'] = $value;
        } else {
            // If array or other format, encrypt it
            $this->attributes['encrypted_descriptor'] = Crypt::encrypt($value);
        }
    }

    /**
     * Decrypt face descriptor
     */
    public function getDecryptedDescriptor()
    {
        try {
            return Crypt::decrypt($this->encrypted_descriptor);
        } catch (\Exception $e) {
            throw new \Exception('Failed to decrypt face descriptor');
        }
    }

    /**
     * Update usage statistics
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Check if face data is expired (optional feature)
     */
    public function isExpired(int $days = 365): bool
    {
        return $this->created_at->diffInDays(now()) > $days;
    }

    /**
     * Activate face login
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate face login
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Verify face data by admin
     */
    public function verify(): bool
    {
        return $this->update(['is_verified' => true]);
    }

    /**
     * Get active and verified face data for user
     */
    public static function getActiveForUser(int $userId)
    {
        return self::where('user_id', $userId)
            ->where('is_active', true)
            ->where('is_verified', true)
            ->first();
    }

    /**
     * Scope for active face data
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for verified face data
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Get registration info for security
     */
    public function getRegistrationInfo(): array
    {
        return [
            'ip' => $this->registration_ip,
            'user_agent' => $this->registration_user_agent,
            'registered_at' => $this->created_at,
            'last_used' => $this->last_used_at,
            'usage_count' => $this->usage_count
        ];
    }
}
