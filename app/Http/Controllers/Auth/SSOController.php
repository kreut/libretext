<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Exceptions\Handler;
use \Exception;
use App\Http\Requests\FinishRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Traits\Registration;

class SSOController extends Controller
{
    use Registration;

    public function finishRegistration(FinishRegistration $request){

        $response['type'] = 'error';
        try {
            $data = $request->validated();
            DB::beginTransaction();
            [$course_id, $role] = $this->setRole($data);
            $user = Auth::user();
            $user->role = $role;
            $user->time_zone = $data['time_zone'];
            $user->save();
            if ($role === 4) {
                $this->addGraderToCourse($user->id, $course_id);
            }
            DB::commit();
            $response['type'] = 'success';
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
