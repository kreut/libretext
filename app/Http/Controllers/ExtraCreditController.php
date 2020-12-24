<?php

namespace App\Http\Controllers;


use App\Course;
use App\ExtraCredit;
use App\Http\Requests\StoreExtraCredit;
use App\User;

use App\Exceptions\Handler;
use \Exception;


use Illuminate\Support\Facades\Gate;
use \Illuminate\Http\Request;

class ExtraCreditController extends Controller
{

    public function show(Request $request, Course $course, User $user, ExtraCredit $extraCredit){
        $response['type'] = 'error';

        $extra_credit = ExtraCredit::where('course_id', $course->id)
                                    ->where('user_id', $user->id)
                                    ->first();


       $authorized = Gate::inspect('store', [$extraCredit, $course, $user->id]);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }



        try {


            $response['type'] = 'success';
            $response['extra_credit'] = $extra_credit ? $extra_credit['extra_credit'] : '';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the extra credit from the database.  Please try again or contact us for assistance.";
        }
        return $response;


    }



    public function store(StoreExtraCredit $request, ExtraCredit $extraCredit)
    {

        $response['type'] = 'error';
        $course_id = $request->course_id;
        $student_user_id =  $request->student_user_id;
        $course = Course::find($course_id);

       $authorized = Gate::inspect('store', [$extraCredit, $course,$student_user_id]);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }


        try {

            $data = $request->validated();

            ExtraCredit::updateOrCreate(
                [ 'user_id' => $student_user_id, 'course_id' => $course_id],
                ['extra_credit' => $data['extra_credit']]
            );

            $response['type'] = 'success';
            $response['message'] = 'The student has been given extra credit.';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error giving the student extra credit.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
