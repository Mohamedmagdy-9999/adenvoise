<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $guarded = [];
    
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
       
       
    ];

    protected $appends = ['directorate_name','entity_name'];

    /**
     * Relations
     */
     
     public function getJWTIdentifier()
    {
        return $this->getKey(); // عادة user_id
    }

    public function getJWTCustomClaims()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'directorate_name' => $this->directorate_name,
            'entity_name' => $this->entity_name,
         
            
        ];
    }

    public function directorate()
    {
        return $this->belongsTo(Directorate::class , 'directorate_id');
    }

    public function getDirectorateNameAttribute()
    {
         return $this->directorate ? $this->directorate->name : null;
    }


    public function entity()
    {
        return $this->belongsTo(Entity::class , 'entity_id');
    }

    public function getEntityNameAttribute()
    {
         return $this->entity ? $this->entity->name : null;
    }
    
    
   
}
