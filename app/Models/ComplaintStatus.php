<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplaintStatus extends Model
{
    use HasFactory;
    protected $appends = ['name'];

    public function getNameAttribute()
    {
        $locale = app()->getLocale();

        return $locale == 'ar'
            ? $this->name_ar
            : $this->name_en;
    }
}
