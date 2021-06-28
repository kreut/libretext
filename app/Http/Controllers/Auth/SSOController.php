<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Exceptions\Handler;
use \Exception;
use App\Http\Requests\FinishRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;

use App\Traits\Registration;

class SSOController extends Controller
{
    use Registration;

    public function completedRegistration(Request $request){
        $roles = [2 =>'instructor', 3=>'student', 4=>'grader'];
        $registration_type = Auth::user()->role ?$roles[Auth::user()->role ] : false;
        $response['registration_type'] = $registration_type;//has some role
        $response['landing_page'] = $request->session()->get('landing_page');

        return $response;
    }

    public function isSSOUser(){
        $is_sso_user = DB::table('oauth_providers')->where('user_id', Auth::user()->id)->get();
        $response['is_sso_user'] = $is_sso_user->isNotEmpty();
        return $response;
    }

    public function finishRegistration(FinishRegistration $request){

        $response['type'] = 'error';
        try {
            $data = $request->validated();
            DB::beginTransaction();
            [$course_id, $role] = $this->setRole($data);
            $user = Auth::user();
            $user->role = $role;
            if ($role === 3) {
                $user->student_id = $request->student_id;
            }
            $user->time_zone = $data['time_zone'];
            $user->save();
            if ($role === 4) {
                $this->addGraderToCourse($user->id, $course_id);
            }
            DB::commit();
            $response['type'] = 'success';
            $response['landing_page'] = $request->session()->get('landing_page');
            $request->session()->flash('completed_sso_registration', true);
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to complete your registration.  Please try again or contact us for assistance.";
        }
        return $response;


    }
}
