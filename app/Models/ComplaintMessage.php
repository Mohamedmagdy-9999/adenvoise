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
        return asset('messages/' . $this->attachment);
    }

    
}
