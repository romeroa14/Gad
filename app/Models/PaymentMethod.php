<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    // Dependiendo de cómo quieras usar este modelo, puedes relacionarlo con facturas u otros modelos.
    public function bills()
    {
        return $this->hasMany(Bill::class);
    }
}
