<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplaintMessage extends Model
{
    use HasFactory;

     protected $guarded = [];
     protected $appends =['attachment_url'];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class,'complaint_id');
    }


    public function getAttachmentUrlAttribute()
    {
        if ($this->attachment){
            return asset('messages/' . $this->attachment);
        }
        return null;
            
    }

    
}
