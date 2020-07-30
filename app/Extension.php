<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Extension extends Model
{
    protected $fillable = ['user_id', 'assignment_id', 'extension'];
}
