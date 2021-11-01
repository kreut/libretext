<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Http\Requests\EmailInstructorAccessCodeRequest;
use App\Http\Requests\StoreInstructorAccessCodes;
use App\InstructorAccessCode;
use App\Traits\AccessCodes;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;


class InstructorAccessCodeController extends Controller
{
    use AccessCodes;

    /**
     * @param StoreInstructorAccessCodes $request
     * @param InstructorAccessCode $instructorAccessCode
     * @return array
     * @throws Exception
     */
    public function store(StoreInstructorAccessCodes $request, InstructorAccessCode $instructorAccessCode): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $instructorAccessCode);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $data = $request->validated();
        try {
            $instructor_access_codes = [];
            $number_access_codes = 0;
            while ($number_access_codes < $data['number_of_instructor_access_codes']) {
                $instructor_access_code = $this->createInstructorAccessCode();
                if (!$instructorAccessCode->where('access_code', $instructor_access_code)->exists()) {
                    $instructorAccessCode = new InstructorAccessCode();
                    $instructorAccessCode->access_code = $instructor_access_code;
                    $instructorAccessCode->save();
                    $instructor_access_codes[] = $instructor_access_code;
                    $number_access_codes++;
                }
            }
            $response['instructor_access_codes'] = $instructor_access_codes;
            $response['message'] = "The instructor access codes have been created.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to create the new instructor access codes.  Please try again.";
        }
        return $response;
    }

    /**
     * @param EmailInstructorAccessCodeRequest $request
     * @param InstructorAccessCode $instructorAccessCode
     * @return array
     * @throws Exception
     */
    public function email(EmailInstructorAccessCodeRequest $request, InstructorAccessCode $instructorAccessCode): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('email', $instructorAccessCode);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $data = $request->validated();
        try {
            DB::beginTransaction();
            $instructorAccessCode = new InstructorAccessCode();
            $instructorAccessCode->access_code = $this->createInstructorAccessCode();
            $instructorAccessCode->save();


            $beauty_mail = app()->make(\Snowfire\Beautymail\Beautymail::class);

            $to_email = $data['email'];
            $access_code_link = request()->getSchemeAndHttpHost() . "/register/instructor/$instructorAccessCode->access_code";
            $beauty_mail->send('emails.instructor_access_code', ['access_code' => $instructorAccessCode->access_code,'access_code_link' => $access_code_link], function ($message)
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
}

