<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplaintRate extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class,'complaint_id');
    }

    public function citizen()
    {
        return $this->belongsTo(Citizen::class,'citizen_id');
    }
}
