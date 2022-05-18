<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\QtiImport;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QtiJobController extends Controller
{
    /**
     * @param Request $request
     * @param QtiImport $qtiImport
     * @return array
     * @throws Exception
     */
    public function getStatus(Request $request, QtiImport $qtiImport): array
    {
        $response['type'] = 'error';
        try {
            if (!$request->qti_job_id) {
                $response['status'] = 'error';
                $response['message'] = "No QTI job ID was present.";
                return $response;
            }
            $job = DB::table('qti_jobs')->where('id', $request->qti_job_id)->first();
            if (!$job) {
                $response['type'] = 'success';
                $response['status'] = 'processing';
                $response['message'] = "Processing file...";
                return $response;
            }
            if ($job->status === 'completed') {
                $questions_to_import_info = $qtiImport->where('qti_job_id',  $request->qti_job_id)->get();
                $questions_to_import = [];
                foreach ($questions_to_import_info as $question_to_import) {
                    $questions_to_import[] = ['identifier' => $question_to_import->identifier, 'title' => '...', 'import_status' => 'Pending'];
                }
                $response['questions_to_import'] = $questions_to_import;
            }
            $response['type'] = 'success';
            $response['status'] = $job->status;
            $response['message'] = $job->message;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get the status of the import.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}
