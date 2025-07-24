<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GlobalStatus;

class Conversation extends Model
{
    use GlobalStatus;

    protected $fillable = [
        'job_id',
        'buyer_id',
        'user_id',
    ];
    public function job()
    {
        return $this->belongsTo(Job::class);
    }


    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function scopeUnblock($query)
    {
        return $query->where('status', Status::UNBLOCK);
    }

   
}
