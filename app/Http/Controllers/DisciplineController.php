<?php

namespace App\Http\Controllers;

use App\Course;
use App\Discipline;
use App\Exceptions\Handler;
use App\Http\Requests\StoreDisciplineRequest;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DisciplineController extends Controller
{
    /**
     * @param Discipline $discipline
     * @return array
     * @throws Exception
     */
    public function index(Discipline $discipline): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('index', $discipline);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
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
            $course->where('discipline_id', $discipline->id)->update(['discipline_id'=> null]);
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
