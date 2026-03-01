<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplaintAttachment extends Model
{
    use HasFactory;
    protected $table = "complaint_attachments";
    protected $fillable = [
    'complaint_id',
    'file',
    'type',
];

    protected $appends = ['file_url'];

    public function getFileUrlAttribute()
    {
        return asset('complaints/' . $this->file);
    }
}
