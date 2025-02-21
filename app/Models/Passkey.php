<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\JsonSerializer;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webauthn\PublicKeyCredentialSource;
class Passkey extends Model
{
    protected $fillable = [
        'name',
        'credential_id',
        'user_id',
        'data',
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS / MUTATORS
    |--------------------------------------------------------------------------
    */
    public function data(): Attribute
    {
        return new Attribute(
            get: fn (string $value) => JsonSerializer::deserialize($value, PublicKeyCredentialSource::class),
            set: fn (PublicKeyCredentialSource $value) => [
                'credential_id' => $value->publicKeyCredentialId,
                'data' => JsonSerializer::serialize($value),
            ],
        );
    }
}
