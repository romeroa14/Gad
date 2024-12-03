<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    public function adsCampaigns()
    {
        return $this->hasMany(AdsCampaign::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function accountReceivables()
    {
        return $this->hasMany(AccountReceivable::class);
    }
}
