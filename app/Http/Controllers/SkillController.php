<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Skill;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SkillController extends Controller
{

    public function index(Skill $skill)
    {

        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('index', $skill);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $response['skills'] = DB::table('skills')->get('title')->pluck('title');
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error showing the solution.  Please try again or contact us for assistance.";
            return $response;
        }
        return $response;
    }

}
