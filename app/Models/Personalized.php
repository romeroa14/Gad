<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personalized extends Model
{
    public function getName()
    {
        return $this->name ?? 'Personalizado #' . $this->id;
    }

    public function services()
    {
        return $this->morphMany(Service::class, 'serviceable');
    }
}
