<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplaintParties extends Model
{
    use HasFactory;

    protected $appends = ['entity_name'];


    public function entity()
    {
        return $this->belongsTo(Entity::class , 'entity_id');
    }

    public function getEntityNameAttribute()
    {
         return $this->entity ? $this->entity->name : null;
    }
}
