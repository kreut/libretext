<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuestionMediaController extends Controller
{
    public function index(string $media)
    {
        $temporary_url = Storage::disk('s3')->temporaryUrl("uploads/question-media/$media", Carbon::now()->addDays(7));
        return view('question_media',['temporary_url'=>$temporary_url]);
    }
}
