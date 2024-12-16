<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'credentials',
        'status'
    ];

    protected $casts = [
        'credentials' => 'encrypted',
    ];

    public function getMetaCredentials()
    {
        if ($this->credentials) {
            return json_decode(decrypt($this->credentials), true);
        }
        return null;
    }

    public function setMetaCredentials($credentials)
    {
        $this->credentials = encrypt(json_encode($credentials));
        $this->save();
    }
} 