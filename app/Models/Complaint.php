<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $appends = ['complaint_type_name','type_name','level_name','directorate_name','neighborhood_name','entity_name','status_name','citizen_name'];

    public function attachments()
    {
        return $this->hasMany(ComplaintAttachment::class, 'complaint_id');
    }

    public function complaint_type()
    {
        return $this->belongsTo(ComplaintType::class , 'complaint_type_id');
    }

    public function getComplaintTypeNameAttribute()
    {
         return $this->complaint_type ? $this->complaint_type->name : null;
    }

    public function type()
    {
        return $this->belongsTo(Type::class , 'type_id');
    }

    public function getTypeNameAttribute()
    {
         return $this->type ? $this->type->name : null;
    }

    public function level()
    {
        return $this->belongsTo(SpeelLevel::class , 'speel_level_id');
    }

    public function getLevelNameAttribute()
    {
         return $this->level ? $this->level->name : null;
    }

    public function directorate()
    {
        return $this->belongsTo(Directorate::class , 'directorate_id');
    }

    public function getDirectorateNameAttribute()
    {
         return $this->directorate ? $this->directorate->name : null;
    }

    public function neighborhood()
    {
        return $this->belongsTo(Neighborhood::class , 'neighborhood_id');
    }

    public function getNeighborhoodNameAttribute()
    {
         return $this->neighborhood ? $this->neighborhood->name : null;
    }

    public function entity()
    {
        return $this->belongsTo(Entity::class , 'entity_id');
    }

    public function getEntityNameAttribute()
    {
         return $this->entity ? $this->entity->name : null;
    }


    public function status()
    {
        return $this->belongsTo(ComplaintStatus::class , 'complaint_status_id');
    }

    public function getStatusNameAttribute()
    {
         return $this->status ? $this->status->name : null;
    }

    public function citizen()
    {
        return $this->belongsTo(Citizen::class , 'citizen_id');
    }

    public function getCitizenNameAttribute()
    {
         return $this->citizen ? $this->citizen->name : null;
    }
    
    public function messages()
    {
        return $this->hasMany(ComplaintMessage::class,'complaint_id')->latest();
    }

}
