<?php

namespace App\Http\Controllers;

use App\Solution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SolutionController extends Controller
{
    public function storeSolutionFile(Request $request, Solution $Solution)
    {

        $response['success'] = false;
        $question_id = $request->questionId;
        $user_id = Auth::user()->id;
        $file = $request->file("solutionFile")->store("solutions/$user_id/$question_id", 'local');
        $solutionContents = Storage::disk('local')->get($file);

        Storage::disk('s3')->put($file, $solutionContents, ['StorageClass' => 'STANDARD_IA']);
        $original_filename = $request->file("solutionFile")->getClientOriginalName();
        $file_data = [
            'file' => basename($file),
            'original_filename' => $original_filename,
            'updated_at' => Carbon::now()];
        $Solution->updateOrCreate(
            ['user_id' => $user_id,
                'question_id' => $question_id],
            $file_data
        );
        $response['success'] = true;
        $response['message'] = 'Your solution has been saved.';
        $response['original_filename'] = $original_filename;
        return $response;

    }
}
