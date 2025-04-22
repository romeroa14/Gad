<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'instagram_id',
        'username',
        'name',
        'biography',
        'followers_count',
        'follows_count',
        'media_count',
        'profile_picture_url',
        'website',
        'is_private',
        'is_verified',
        'facebook_page_id',
        'advertising_account_id',
        'ad_account_id',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'followers_count' => 'integer',
        'follows_count' => 'integer',
        'media_count' => 'integer',
        'is_private' => 'boolean',
        'is_verified' => 'boolean',
    ];

    /**
     * Obtener el cliente asociado a esta cuenta de Instagram.
     */
    public function client()
    {
        return $this->hasOne(Client::class);
    }

    /**
     * Obtener la cuenta publicitaria asociada a esta cuenta de Instagram.
     */
    public function advertisingAccount()
    {
        return $this->belongsTo(AdvertisingAccount::class);
    }

    /**
     * Obtener la página de Facebook conectada a esta cuenta de Instagram.
     */
    public function facebookPage()
    {
        return $this->belongsTo(FacebookPage::class, 'facebook_page_id', 'page_id');
    }

    

    /**
     * Comprueba si la cuenta está verificada.
     */
    public function isVerified()
    {
        return $this->is_verified;
    }

    /**
     * Comprueba si la cuenta es privada.
     */
    public function isPrivate()
    {
        return $this->is_private;
    }

    /**
     * Obtiene la URL de la imagen de perfil.
     */
    public function getProfilePictureUrl()
    {
        return $this->profile_picture_url ?? 'https://via.placeholder.com/150?text=IG';
    }
} 