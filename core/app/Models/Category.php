<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Traits\GlobalStatus;

class Category extends Model
{
    use GlobalStatus;
    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
}
