<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Citizen extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $guarded = [];
    protected $appends = ['image_url','directorate_name','neighborhood_name'];
    protected $hidden = [
        'password',
        'remember_token',
        'test',
    ];

    // ✅ مطلوب من JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // ✅ مطلوب من JWT
    public function getJWTCustomClaims()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'identity_number' => $this->identity_number,
            'image' => $this->image_url,
            'directorate_id' => $this->directorate_id,
            'directorate_name' => $this->directorate ? $this->directorate->name_ar : null,
            'neighborhood_id' => $this->neighborhood_id,
            'neighborhood_name' => $this->neighborhood ? $this->neighborhood->name_ar : null,
            
        ];
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('citizen/' . $this->image);
        }
        return null;
    }

    public function directorate()
    {
        return $this->belongsTo(Directorate::class,'directorate_id');
    }

    public function getDirectorateNameAttribute()
    {
        return $this->directorate ? $this->directorate->name_ar : null;
    }

    public function neighborhood()
    {
        return $this->belongsTo(Neighborhood::class,'neighborhood_id');
    }

    public function getNeighborhoodNameAttribute()
    {
        return $this->neighborhood ? $this->neighborhood->name_ar : null;
    }
}