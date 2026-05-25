<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class RegistrationToken extends Model
{
    protected $fillable = [
        'token',
        'is_active',
        'expires_at',
        'used_at'
    ];

    private static function generateUniqueTokenString(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $candidate = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            if (!self::where('token', $candidate)->exists()) {
                return $candidate;
            }
        }

        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get the latest active token or generate a new one if none exists or has expired.
     */
    public static function getLatestToken()
    {
        $token = self::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        if (!$token) {
            // Deactivate all active tokens first
            self::where('is_active', true)->update(['is_active' => false]);

            $token = null;
            for ($attempt = 0; $attempt < 5 && !$token; $attempt++) {
                try {
                    $token = self::create([
                        'token' => self::generateUniqueTokenString(),
                        'is_active' => true,
                        'expires_at' => now()->addMinutes(10),
                    ]);
                } catch (QueryException $e) {
                    if ($attempt === 4) {
                        throw $e;
                    }
                }
            }

            // Hapus 5 data sebelumnya jika sudah mencapai data ke-6 (menjaga agar database tidak penuh)
            if (self::count() > 5) {
                self::where('id', '<', $token->id)->delete();
            }
        }

        return $token;
    }

    /**
     * Accessor for formatted token (e.g., 123 456)
     */
    public function getFormattedTokenAttribute()
    {
        if (strlen($this->token) === 6) {
            return substr($this->token, 0, 3) . ' ' . substr($this->token, 3);
        }
        return $this->token;
    }
}
