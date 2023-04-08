<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\RubricCategorySubmission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OpenAIController extends Controller
{
    /**
     * @param Request $request
     * @param string $type
     * @param RubricCategorySubmission $rubricCategorySubmission
     * @return array
     * @throws Exception
     */
    public function results(Request $request, string $type, RubricCategorySubmission $rubricCategorySubmission): array
    {
        $response['type'] = 'error';
        $token = $request->bearerToken();
        if ($token && ($token === config('myconfig.my_essay_editor_token'))) {
            try {
                switch ($request->type) {
                    case('lab-report'):
                        $rubricCategorySubmission->where('user_id', $request->user_id)
                            ->where('rubric_category_id', $request->rubric_category_id)
                            ->update(['status' => $request->status, 'message' => $request->message]);
                        break;
                    default:
                        throw new Exception ("$type is not a valid type to process.");

                }
                $response['type'] = 'success';
                $response['message'] = 'received';
            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);
                $response['type'] = 'error';
                $response['message'] = $e->getMessage();
            }
        } else {
            $response['message'] = "Not authorized for processing the AI results using token: $token" ;
        }
        return $response;
    }
}
