<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class H5pCollection extends Model
{
    /**
     * @param int $h5p_id
     * @param string $email
     * @return array
     */
    public function getAdaptIdByH5pId(int $h5p_id, string $email): array
    {
        $response['type'] = 'error';
        if (!$email) {
            $response['message'] = 'An email address is missing from the request.';
            return $response;
        }

        $questions = DB::table('questions')
            ->where('technology', 'h5p')
            ->where('technology_id', $h5p_id)
            ->select('questions.id AS question_id', 'questions.question_editor_user_id AS user_id')
            ->get();
        $user_ids = [];
        $users_by_id = [];

        foreach ($questions as $question) {
            $user_ids[] = $question->user_id;
        }
        $users = DB::table('users')
            ->whereIn('id', $user_ids)
            ->get();

        foreach ($users as $user) {
            $users_by_id[$user->id] = $user;
        }

        foreach ($questions as $key => $question) {
            if (!$question->user_id){
                $response['message'] = 'There is no user associated with this question.';
                return $response;
            }
            $questions[$key]->email = $users_by_id[$question->user_id]->email;
        }


        if (!$questions) {
            $response['message'] = 'There is no ADAPT question with that H5P ID.';
            return $response;
        }
        if ($questions->count() === 1) {
            $response['type'] = 'success';
            $response['adapt_question_id'] = $questions[0]->question_id;
        } else {
            $email_lines_up = false;
            $adapt_question_id = 0;
            foreach ($questions as $question) {
                $question_email = strtolower(trim($question->email));
                $request_email = strtolower(trim($email));
                if (!$email_lines_up && $question_email === $request_email) {
                    $email_lines_up = true;
                    $adapt_question_id = $question->question_id;
                }
            }
            if ($adapt_question_id) {
                $response['type'] = 'success';
                $response['adapt_question_id'] = $adapt_question_id;
            } else {
                $response['type'] = 'error';
                $response['message'] = 'There are no ADAPT IDs with that H5P ID and that email address.';
            }
        }

        return $response;
    }
}
