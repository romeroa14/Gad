<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    public function getName()
    {
        return $this->name ?? 'Plan #' . $this->daily_investment . ' ' . $this->duration;
    }

    protected $fillable = [
        'daily_investment',
        'duration',
        'scope',
        'investment',
        'price',
        
    ];

    public function adsCampaigns()
    {
        return $this->hasMany(AdsCampaign::class);
    }

    public function services()
    {
        return $this->morphMany(Service::class, 'serviceable');
    }
}
