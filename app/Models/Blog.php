<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;
   
    protected $guarded = [];

    protected $appends = ['image_url','title','category_name','desc'];

    public function getFileUrlAttribute()
    {
        return asset('blog/' . $this->file);
    }

    public function getTitleAttribute()
    {
        $locale = app()->getLocale();

        return $locale == 'ar'
            ? $this->title_ar
            : $this->title_en;
    }

    public function getDescAttribute()
    {
        $locale = app()->getLocale();

        return $locale == 'ar'
            ? $this->desc_ar
            : $this->desc_en;
    }

    public function category()
    {
        return $this->belongsTo(Category::class , 'category_id');
    }

    public function getCategoryNameAttribute()
    {
         return $this->category ? $this->category->name : null;
    }
}
