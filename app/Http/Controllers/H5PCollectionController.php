<?php

namespace App\Http\Controllers;

use App\AssignmentTemplate;
use App\Course;
use App\H5pCollection;
use App\Http\Requests\H5PCollectionImport;
use Exception;
use Illuminate\Http\Request;
use App\Exceptions\Handler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class H5PCollectionController extends Controller
{
    /**
     * @param Request $request
     * @param int $h5p_id
     * @param H5pCollection $h5pCollection
     * @return array
     */
    public function getAdaptIdByH5pId(Request       $request,
                                      int           $h5p_id,
                                      H5pCollection $h5pCollection): array
    {

        if (!($request->bearerToken() && $request->bearerToken() === config('myconfig.h5p_api_password'))) {
            $response['type'] = 'error';
            $response['message'] = 'Invalid bearer token.';
            return $response;
        }

        return $h5pCollection->getAdaptIdByH5pId($h5p_id, $request->email);

    }
    /**
     * @param H5pCollection $h5PCollection
     * @return array
     * @throws Exception
     */
    public function index(H5PCollection $h5PCollection): array
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('index', $h5PCollection);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $endpoint = "https://studio.libretexts.org/api/collections/list";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            $output = curl_exec($ch);
            $error_msg = curl_errno($ch) ? curl_error($ch) : '';
            curl_close($ch);

            if ($error_msg) {
                throw new Exception ("Import All H5P collections failed: $error_msg");
            }

            $used_cids = [];
            $collections = [];
            foreach (json_decode($output, 1) as $collection) {
                if (!in_array($collection['cid'], $used_cids)) {
                    $collections[] = $collection;
                    $used_cids[] = $collection['cid'];
                }
            }
            usort($collections, function ($a, $b) {
                return $a['title'] <=> $b['title'];
            });
            $response['collections'] = $collections;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve the H5P collections.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    /**
     * @param H5PCollectionImport $request
     * @param AssignmentTemplate $assignmentTemplate
     * @param H5pCollection $h5PCollection
     * @return array
     * @throws Exception
     */
    public function validateImport(H5PCollectionImport $request,
                                   AssignmentTemplate  $assignmentTemplate,
                                   H5PCollection       $h5PCollection): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('validateImport', [
            $h5PCollection,
            $request->folder_id,
            $request->import_to_course,
            $request->assignment_template
        ]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $endpoint = "https://studio.libretexts.org/api/collections/$request->collection";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            $output = curl_exec($ch);
            $error_msg = curl_errno($ch) ? curl_error($ch) : '';
            curl_close($ch);

            if ($error_msg) {
                throw new Exception ("Import All H5P collection $request->collection failed: $error_msg");
            }
            $sub_collections = [];
            $questions_to_import = [];
            DB::beginTransaction();
            $question_infos = json_decode($output, 1);
            foreach ($question_infos as $question_info) {
                $title = $question_info['subcollection_title'];
                $assignment_id = 0;
                if ($request->import_to_course) {
                    $assignment = DB::table('assignments')
                        ->where('course_id', $request->import_to_course)
                        ->where('name', $title)
                        ->first();
                    if (!$assignment) {
                        if (!in_array($title, $sub_collections)) {
                            $sub_collections[] = $title;
                            $assignment = $assignmentTemplate->createAssignmentFromTemplate(Course::find($request->import_to_course),
                                $request->user()->id,
                                $request->assignment_template,
                                $title);
                        }
                    }
                    $assignment_id = $assignment->id;
                }
                $questions_to_import[] = [
                    'assignment_id' => $assignment_id,
                    'id' => $question_info['h5p_id'],
                    'import_status' => 'Pending',
                    'title' => 'N/A',
                    'author' => 'N/A',
                    'tags' => 'N/A'
                ];
            }
            $response['questions_to_import'] = $questions_to_import;
            $response['type'] = 'success';
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to validate the H5P collection.  Please try again or contact us for assistance.";
        }
        return $response;


    }


}
