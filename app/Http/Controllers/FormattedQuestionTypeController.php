<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\FormattedQuestionType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FormattedQuestionTypeController extends Controller
{
    /**
     * @param FormattedQuestionType $formattedQuestionType
     * @return array
     * @throws Exception
     */
    public function index(FormattedQuestionType $formattedQuestionType): array
    {

        try {

            $response['formatted_question_types'] = $formattedQuestionType->allFormattedQuestionTypes();
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the formatted question types.  Please try again or contact us for assistance.";
        }

        return $response;


    }
}
