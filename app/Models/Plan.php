<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    public function adsCampaigns()
    {
        return $this->hasMany(AdsCampaign::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }
}
