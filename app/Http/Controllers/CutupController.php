<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Cutup;
use Illuminate\Http\Request;
use App\Exceptions\Handler;
use \Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Traits\S3;

class CutupController extends Controller
{

    use S3;

    public function show(Request $request, Assignment $assignment, Cutup $cutup)
    {

        $user_id = Auth::user()->id;
        $response['type'] = 'error';
        /* $authorized = Gate::inspect('view', $enrollment);

         if (!$authorized->allowed()) {
             $response['message'] = $authorized->message();
             return $response;
         }*/
        try {
            $cutups = [];
            $results = $cutup->where('assignment_id', $assignment->id)
                ->where('user_id', $user_id)
                ->orderBy('id', 'asc')
                ->get();

            if ($results->isNotEmpty()) {
                foreach ($results as $key => $value) {
                    $cutups[] = [
                        'id' => $value->id,
                        'temporary_url' => \Storage::disk('s3')->temporaryUrl("cutups/$user_id/$value->file", now()->addMinutes(120))
                    ];
                }
            }
            $response['type'] = 'success';
            $response['cutups'] = $cutups;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your cutups.  Please try again or contact us for assistance.";
        }
        return $response;

    }

}
