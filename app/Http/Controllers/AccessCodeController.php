<?php

namespace App\Http\Controllers;

use App\AccessCode;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Http\Requests\EmailAccessCodeRequest;
use App\Http\Requests\StoreAccessCodes;
use App\InstructorAccessCode;
use App\QuestionEditorAccessCode;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;


class AccessCodeController extends Controller
{

    /**
     * @param StoreAccessCodes $request
     * @param AccessCode $AccessCode
     * @return array
     * @throws Exception
     */
    public function store(StoreAccessCodes $request, AccessCode $AccessCode): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $AccessCode);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $data = $request->validated();
        try {

           $model = $this->_getModel($request);
            if (!$model){
                $response['message'] = "$request->type is not a valid type of access code.";
                return $response;
            }
            $access_codes = [];
            $number_access_codes = 0;
            while ($number_access_codes < $data['number_of_access_codes']) {
                $access_code = Helper::createAccessCode();
                $model = $this->_getModel($request);
                if (!$model->where('access_code', $access_code)->exists()) {
                    $model->access_code = $access_code;
                    $model->save();
                    $access_codes[] = $access_code;
                    $number_access_codes++;
                }
            }

            $response['access_codes'] = $access_codes;
            $response['message'] = "The access codes have been created.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to create the new access codes.  Please try again.";
        }
        return $response;
    }

    /**
     * @param EmailAccessCodeRequest $request
     * @param AccessCode $AccessCode
     * @return array
     * @throws Exception
     */
    public function email(EmailAccessCodeRequest $request, AccessCode $AccessCode): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('email', $AccessCode);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $model = $this->_getModel($request);
        if (!$model){
            $response['message'] = "$request->type is not a valid type of access code.";
        }
        $data = $request->validated();
        try {
            DB::beginTransaction();
            $access_code = Helper::createAccessCode();
            $model->access_code = $access_code;
            $model->save();

            $beauty_mail = app()->make(\Snowfire\Beautymail\Beautymail::class);

            $to_email = $data['email'];
            $access_code_link = request()->getSchemeAndHttpHost() . "/register/instructor/$access_code";
            $beauty_mail->send('emails.instructor_access_code', ['access_code' =>  $access_code,'access_code_link' => $access_code_link], function ($message)
            use ($to_email) {
                $message
                    ->from('adapt@noreply.libretexts.org', 'Adapt')
                    ->to($to_email)
                    ->subject("Instructor Access Code")
                    ->replyTo('delmar@libretexts.org');
            });
            DB::commit();
            $response['type'] = 'success';
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
        }
        return $response;


    }
    private function _getModel($request){
        switch($request->type){
            case('instructor'):
                $model = new InstructorAccessCode();
                break;
            case('question editor'):
                $model = new QuestionEditorAccessCode();
                break;
            default:
                $model = null;
        }
        return $model;
    }
}

