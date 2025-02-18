<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\H5pActivitySet;
use App\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use SebastianBergmann\ObjectReflector\Exception;

/**
 *
 */
class H5pActivitySetController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param H5pActivitySet $h5pActivitySet
     * @return array
     * @throws \Exception
     */
    public function getSubmissions(Request             $request,
                         Assignment          $assignment,
                         Question            $question,
                         H5pActivitySet $h5pActivitySet): array
    {

        $response['type'] = 'error';
        try {

            $response['h5p_activity_set_submissions'] = $h5pActivitySet->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->orderBy('id')
                ->get();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve your H5P video interactions for this question.  Please try again or contact us for assistance.";
        }

        return $response;

    }
}
