<?php

namespace App\Http\Controllers;

use App\Course;
use App\Discipline;
use App\Exceptions\Handler;
use App\Http\Requests\RequestNewDisciplineRequest;
use App\Http\Requests\StoreDisciplineRequest;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Snowfire\Beautymail\Beautymail;

class DisciplineController extends Controller
{
    /**
     * @param RequestNewDisciplineRequest $request
     * @param Discipline $discipline
     * @return array
     * @throws Exception
     */
    public function requestNew(RequestNewDisciplineRequest $request, Discipline $discipline): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('requestNew', $discipline);
            $data = $request->validated();
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $beauty_mail = app()->make(Beautymail::class);
            $to_email = 'jhalpern@libretexts.org';
            $reply_to_email = $request->user()->email;
            $requested_by = $request->user()->first_name . ' ' . $request->user()->last_name;

            $beauty_mail->send('emails.new_discipline_request',
                ['discipline' => $data['name'], 'requested_by' => $requested_by], function ($message)
                use ($to_email, $reply_to_email) {
                    $message
                        ->from('adapt@noreply.libretexts.org', 'ADAPT')
                        ->to($to_email)
                        ->replyTo($reply_to_email)
                        ->subject('New Discipline Request');
                });
            $response['message'] = "Your request has been submitted.  We'll contact you if we have any questions.";
            $response['type'] = 'success';


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We could not request a new discipline.  Please contact support.";

        }
        return $response;
    }

    /**
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public function index(Course $course): array
    {
        try {
            $response['type'] = 'error';

            $last_course = request()->user() ? $course->where('user_id', request()->user()->id)->orderBy('id', 'DESC')->first() : null;
            $response['discipline'] = $last_course ? $last_course->discipline_id : null;
            $response['disciplines'] = DB::table('disciplines')->orderBy('name')->get();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We could not retrieve the disciplines.  Please contact support.";

        }
        return $response;
    }

    /**
     * @param StoreDisciplineRequest $request
     * @param Discipline $discipline
     * @return array
     * @throws Exception
     */
    public function store(StoreDisciplineRequest $request, Discipline $discipline): array
    {
        try {
            $response['type'] = 'error';
            $data = $request->validated();
            $authorized = Gate::inspect('store', $discipline);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            Discipline::create($data);
            $response['type'] = 'success';
            $response['message'] = 'The discipline has been added.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We could not store the discipline.  Please contact support.";
        }
        return $response;

    }

    /**
     * @param StoreDisciplineRequest $request
     * @param Discipline $discipline
     * @return array
     * @throws Exception
     */
    public function edit(StoreDisciplineRequest $request, Discipline $discipline): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('update', $discipline);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $data = $request->validated();
            $discipline->name = $data['name'];
            $discipline->save();
            $response['type'] = 'success';
            $response['message'] = 'The discipline has be updated.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We could not update the discipline.  Please contact support.";

        }
        return $response;

    }

    /**
     * @param Discipline $discipline
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public function destroy(Discipline $discipline, Course $course): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('destroy', $discipline);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            DB::beginTransaction();
            $course->where('discipline_id', $discipline->id)->update(['discipline_id' => null]);
            $discipline_name = $discipline->name;
            $discipline->delete();
            $response['type'] = 'info';
            $response['message'] = "$discipline_name has been deleted.";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We could not delete the discipline.  Please contact support.";
        }
        return $response;

    }
}
