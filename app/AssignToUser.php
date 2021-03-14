<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignToUser extends Model
{
    use HasFactory;

    public function assignToTimings()
    {
        return $this->belongsTo(AssignToTiming::class);
    }

}
