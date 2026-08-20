<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'ui_locale',
        'google_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Los correos del curso van en ruso: son para la alumna, no para nosotros. */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerificarCorreo);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\RestablecerContrasena($token));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
