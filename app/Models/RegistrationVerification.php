<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationVerification extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'gmail',
        'code',
        'code_hash',
        'expires_at',
        'resend_available_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'code',
        'code_hash',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'resend_available_at' => 'datetime',
        ];
    }
}
