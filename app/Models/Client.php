<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'last_name',
        'email',
        'phone',
        'business',
        'country_id',
        'state_id',
        'address',
        'facebook_page_id',
        'instagram_account_id',
    ];

    public function adsCampaigns()
    {
        return $this->hasMany(AdsCampaign::class);
    }

    public function fanpages()
    {
        return $this->hasMany(FanPage::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function accountReceivables()
    {
        return $this->hasMany(AccountReceivable::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function facebookPage()
    {
        return $this->belongsTo(FacebookPage::class);
    }

    public function instagramAccount()
    {
        return $this->belongsTo(InstagramAccount::class);
    }
}
