<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use GlobalStatus;

    public function jobs()
    {
        return $this->belongsToMany(Job::class, 'job_skills', 'skill_id', 'job_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'skill_user', 'skill_id', 'user_id');
    }
}
