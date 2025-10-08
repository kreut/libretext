<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Http\Requests\StoreQuestionSubjectRequest;
use App\QuestionSubject;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class QuestionSubjectController extends Controller
{
    /**
     * @return array
     * @throws Exception
     */
    public function index(): array
    {
        try {
            $response['type'] = 'success';
            $response['question_subjects'] = QuestionSubject::where('name', '<>', '')->orderBy('name')->get();
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to get the question subjects.  Please try again.";
        }
        return $response;
    }

    /**
     * @param string $technology
     * @return array
     * @throws Exception
     */
    public function getByTechnology(string $technology): array
    {
        try {
            $response['type'] = 'error';
            $query = DB::table('questions')
                ->join('question_subjects', 'questions.question_subject_id', '=', 'question_subjects.id');

            if ($technology !== 'any') {
                $query = $query->where('questions.technology', $technology);
            }
            $response['question_subjects'] = $query
                ->where('question_subjects.name', '<>', '')
                ->orderBy('name')
                ->select('question_subjects.id', 'question_subjects.name')
                ->distinct()
                ->get();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to get the question subjects for $technology.  Please try again.";
        }
        return $response;
    }

    /**
     * @param StoreQuestionSubjectRequest $request
     * @param QuestionSubject $questionSubject
     * @return array
     * @throws Exception
     */
    public function store(StoreQuestionSubjectRequest $request, QuestionSubject $questionSubject): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('store', [$questionSubject, 'subject']);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $data = $request->validated();
            $questionSubject->name = $data['name'];
            $questionSubject->save();
            $response['type'] = 'success';
            $response['question_level_id'] = $questionSubject->id;
            $response['message'] = "The subject <strong>{$data['name']}</strong> has been added.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to update the question subject.  Please try again.";

        }
        return $response;
    }

    /**
     * @param StoreQuestionSubjectRequest $request
     * @param QuestionSubject $questionSubject
     * @return array
     * @throws Exception
     */
    public function update(StoreQuestionSubjectRequest $request, QuestionSubject $questionSubject): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('update', [$questionSubject, 'subject']);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $data = $request->validated();
            $original_name = $questionSubject->name;
            $questionSubject->name = $data['name'];
            $questionSubject->save();
            $response['type'] = 'success';
            $response['message'] = "The subject <strong>{$original_name}</strong> has been updated to <strong>{$data['name']}</strong>.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to update the question subject.  Please try again.";

        }
        return $response;
    }
}
