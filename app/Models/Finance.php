<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Finance extends Model
{
    use HasFactory;

    // Campos que pueden ser llenados en la base de datos
    protected $fillable = [
        'type', 
        'amount', 
        'description'
    ];

    /**
     * Relación con Bills (Facturas)
     * Una Finanzas puede estar relacionada con muchas facturas.
     */
    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Relación con AccountReceivables (Cuentas por Cobrar)
     * Una Finanzas puede estar relacionada con muchas cuentas por cobrar.
     */
    public function accountReceivables()
    {
        return $this->hasMany(AccountReceivable::class);
    }

    /**
     * Relación con AccountPayables (Cuentas por Pagar)
     * Una Finanzas puede estar relacionada con muchas cuentas por pagar.
     */
    public function accountPayables()
    {
        return $this->hasMany(AccountPayable::class);
    }
}