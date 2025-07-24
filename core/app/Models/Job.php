<?php

namespace App\Models;

use App\Constants\Status;
use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Job extends Model
{
    use GlobalStatus;

    protected $casts = [
        'skill_ids' => 'array',
        'questions'  => 'object',
    ];
    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function project()
    {
        return $this->hasOne(Project::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'job_skills', 'job_id', 'skill_id');
    }

    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function scopeDrafted($query)
    {
        return $query->where('status', Status::JOB_DRAFT);
    }
    public function scopePublished($query)
    {
        return $query->where('status', Status::JOB_PUBLISH);
    }
    public function scopeProcessing($query)
    {
        return $query->where('status', Status::JOB_PROCESSING);
    }
    public function scopeCompleted($query)
    {
        return $query->where('status', Status::JOB_COMPLETED);
    }
    public function scopeApproved($query)
    {
        return $query->where('is_approved', Status::JOB_APPROVED);
    }
    public function scopeRejected($query)
    {
        return $query->where('is_approved', Status::JOB_REJECTED);
    }
    public function scopePending($query)
    {
        return $query->where('is_approved', Status::NO);
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            $html = '';
            if ($this->status == Status::JOB_PUBLISH) {
                $html = '<span class="badge badge--primary">' . trans("Published") . '</span>';
            } else if ($this->status == Status::JOB_PROCESSING) {
                $html = '<span class="badge badge--warning">' . trans("Processing") . '</span>';
            } else if ($this->status == Status::JOB_COMPLETED) {
                $html = '<span class="badge badge--success">' . trans("Completed") . '</span>';
            }  else if($this->status == Status::JOB_FINISHED) {
                $html = '<span class="badge badge--finish">' . trans("Finished") . '</span>';
            } else {
                $html = '<span class="badge badge--dark">' . trans("Drafted") . '</span>';
            }
            return $html;
        });
    }
}
