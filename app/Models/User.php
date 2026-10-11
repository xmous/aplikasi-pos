<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    public const RECEIPT_ALIGNMENT_LEFT = 'left';
    public const RECEIPT_ALIGNMENT_CENTER = 'center';
    public const RECEIPT_ALIGNMENT_RIGHT = 'right';
    public const RECEIPT_PAPER_SIZE_58 = 58;
    public const RECEIPT_PAPER_SIZE_80 = 80;

    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'receipt_alignment',
        'receipt_paper_size',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'receipt_paper_size' => 'integer',
        ];
    }

    public function preferredReceiptAlignment(): string
    {
        return in_array(
            $this->receipt_alignment,
            [
                self::RECEIPT_ALIGNMENT_LEFT,
                self::RECEIPT_ALIGNMENT_CENTER,
                self::RECEIPT_ALIGNMENT_RIGHT,
            ],
            true
        )
            ? $this->receipt_alignment
            : self::RECEIPT_ALIGNMENT_CENTER;
    }

    public function preferredReceiptPaperSize(): int
    {
        return in_array(
            (int) $this->receipt_paper_size,
            [
                self::RECEIPT_PAPER_SIZE_58,
                self::RECEIPT_PAPER_SIZE_80,
            ],
            true
        )
            ? (int) $this->receipt_paper_size
            : self::RECEIPT_PAPER_SIZE_58;
    }

    public function isRole(string ...$roles): bool
    {
        return $this->hasAnyRole($roles);
    }
}
