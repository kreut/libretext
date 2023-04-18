<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\GradingStyle;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class GradingStyleController extends Controller
{
    /**
     * @param GradingStyle $gradingStyle
     * @return array
     * @throws Exception
     */
    public function index(GradingStyle $gradingStyle): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('index', $gradingStyle);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $response['grading_styles'] = GradingStyle::all();
            $response['type'] = 'success';
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve the grading styles.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
