<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacebookPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'name',
        'access_token',
        'category',
        'followers_count',
        'likes_count',
        'link',
        'picture_url',
        'verification_status',
        'advertising_account_id',
        'ad_account_id',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'followers_count' => 'integer',
        'likes_count' => 'integer',
        'verification_status' => 'boolean',
    ];

    /**
     * Obtener el cliente asociado a esta página de Facebook.
     */
    public function client()
    {
        return $this->hasOne(Client::class);
    }

    /**
     * Obtener la cuenta publicitaria asociada a esta página.
     */
    public function advertisingAccount()
    {
        return $this->belongsTo(AdvertisingAccount::class);
    }

   

    /**
     * Comprueba si la página está verificada.
     */
    public function isVerified()
    {
        return $this->verification_status;
    }

    /**
     * Obtiene la URL de la imagen de perfil de la página.
     */
    public function getPictureUrl()
    {
        return $this->picture_url ?? 'https://via.placeholder.com/150?text=FB';
    }
} 