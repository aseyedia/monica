<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactIntakeSubmission extends Model
{
    protected $guarded = ['id'];

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
