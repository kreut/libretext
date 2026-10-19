<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubmissionFileConversion extends Model
{
    protected $guarded = [];

    const PENDING = 'pending';
    const PROCESSING = 'processing';
    const COMPLETED = 'completed';
    const FAILED = 'failed';
}
