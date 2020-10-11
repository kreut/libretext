<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SolutionController extends Controller
{
    public function storeSolutionFile(Request $request){

        dd("1. Save the file to the database and S3 (Solutions/user/question_id?).
        2. Show it has been saved.
        3. Let the student see it when solutions released
        4. Let the grader see it");


}
}
