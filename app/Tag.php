<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Tag extends Model
{
    protected $fillable = ['tag'];

    public function questions() {
        return $this->belongsToMany('App\Question')->withTimestamps();
    }

    public function getUsableTags($question_ids){
       return  DB::table('tags')
            ->join('question_tag', 'tags.id', '=', 'question_tag.tag_id')
            ->select('tag', 'question_id')
            ->whereIn('question_id', $question_ids)
            ->where('tag', '<>', 'article:topic')
            ->where('tag', '<>', 'showtoc:no')
            ->where('tag', 'NOT LIKE', '%path-library/%')
            ->get();
    }
}
