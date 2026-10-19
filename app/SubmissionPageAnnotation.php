<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubmissionPageAnnotation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'state' => 'array'
    ];
}
