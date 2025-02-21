<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\JsonSerializer;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webauthn\PublicKeyCredentialSource;
class Challenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'challenge',
        'type',  // 'register' or 'login'
        'status', // 'pending' or 'completed'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
