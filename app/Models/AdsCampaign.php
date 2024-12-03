<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdsCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'client_id',
        'plan_id',
        'start_date',
        'end_date',
        'budget',
        'actual_cost',
        'status',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function reportes()
    {
        return $this->hasMany(Reporte::class);
    }
}