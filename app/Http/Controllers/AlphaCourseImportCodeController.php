<?php

namespace App\Http\Controllers;

use App\AlphaCourseImportCode;
use App\Course;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;


class AlphaCourseImportCodeController extends Controller
{
    public function createImportCode()
    {
        return substr(sha1(mt_rand()), 17, 12);
    }

    public function show(Course $course, AlphaCourseImportCode $alphaCourseImportCode)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('show', [$alphaCourseImportCode, $course]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $import_code_info = $alphaCourseImportCode->where('course_id', $course->id)->first();
            if (!$import_code_info) {
                $import_code = $this->createImportCode();
                $alphaCourseImportCode = new AlphaCourseImportCode();
                $alphaCourseImportCode->course_id = $course->id;
                $alphaCourseImportCode->import_code = $import_code;
                $alphaCourseImportCode->save();
            } else {
                $import_code = $import_code_info['import_code'];
            }
            $response['import_code'] = $import_code;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the Alpha Course Import Code.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function refresh(Course $course, AlphaCourseImportCode $alphaCourseImportCode)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('refresh', [$alphaCourseImportCode, $course]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $import_code = $this->createImportCode();
            $alphaCourseImportCode->where('course_id', $course->id)
                ->update(['import_code' => $import_code]);
            $response['import_code'] = $import_code;
            $response['message'] = "The import code has been refreshed.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the Alpha Course Import Code.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}
