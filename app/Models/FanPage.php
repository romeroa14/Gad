<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FanPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'facebook_page_id',
        // 'name',
        'category',
        'picture_url',
        'client_id',
    ];

    /**
     * Relación con el modelo Client.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
