<?php

namespace App\Jobs;

use App\Assignment;
use App\AssignmentTemplate;
use App\Course;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\QtiImport;
use App\QtiJob;
use App\Section;
use App\Traits\AssignmentProperties;
use App\Traits\DateFormatter;
use App\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ProcessValidateQtiFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AssignmentProperties, DateFormatter;


    private $user_id;
    private $qti_file;
    private $qtiJob;
    private $import_to_course;
    private $assignment_template;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(QtiJob $qtiJob, string $qti_file, $import_to_course, $assignment_template)
    {
        $this->qtiJob = $qtiJob;
        $this->qti_file = $qti_file;
        $this->user_id = $this->qtiJob->user_id;
        $this->import_to_course = $import_to_course;
        $this->assignment_template = $assignment_template;
    }

    /**
     * @return false|void
     * @throws FileNotFoundException|Exception
     */
    public function handle()
    {
        $qtiImport = new QtiImport();
        try {
            $dir = "uploads/qti/$this->user_id";
            $path_to_qti_zip = "$dir/$this->qti_file";
            if (!$this->qti_file || !Storage::disk('s3')->exists($path_to_qti_zip)) {
                $this->qtiJob->where('id', $this->qtiJob->id)
                    ->update(['message' => "The QTI file does not exist on our server.", 'status' => 'error']);
                return false;
            }
            $efs_dir = '/mnt/local/';
            $is_efs = is_dir($efs_dir);
            $storage_path = $is_efs
                ? $efs_dir
                : Storage::disk('local')->getAdapter()->getPathPrefix();

            $local_dir = $storage_path . $dir;
            if (!is_dir($storage_path . $dir)) {
                mkdir($local_dir, 0700, true);
            }

            $this->qtiJob->where('id', $this->qtiJob->id)
                ->update(['message' => "Retrieving the file from the server."]);
            file_put_contents("$storage_path$path_to_qti_zip", Storage::disk('s3')->get($path_to_qti_zip));

            $this->qtiJob->where('id', $this->qtiJob->id)
                ->update(['message' => "Unzipping the file."]);

            $zip = new ZipArchive();
            $res = $zip->open("$storage_path$path_to_qti_zip");
            if ($res !== TRUE) {
                $this->qtiJob->where('id', $this->qtiJob->id)
                    ->update(['status' => 'error', 'message' => 'We were not able to unzip your file.']);
                return false;
            }
            // extract it to the path we determined above
            $filename_as_dir = pathinfo($path_to_qti_zip)['filename'];
            $unzipped_dir = "$local_dir/$filename_as_dir";
            if (!is_dir($unzipped_dir)) {
                mkdir($unzipped_dir);
            }
            $zip->extractTo($unzipped_dir);
            $zip->close();
            if (!file_exists("$unzipped_dir/imsmanifest.xml")) {
                $this->qtiJob->where('id', $this->qtiJob->id)
                    ->update(['message' => 'No imsmanifest.xml is present.', 'status' => 'error']);
                return false;
            }

            $xml = simplexml_load_file("$unzipped_dir/imsmanifest.xml");
            $json = json_encode($xml);
            $array = json_decode($json, TRUE);
            $quiz_dirs = [];
            $resources_list = [];

            switch ($this->qtiJob->qti_source) {
                case('canvas'):
                    $resources = $array['resources']['resource'];
                    foreach ($resources as $resource) {
                        if ($resource['@attributes']['type'] === 'associatedcontent/imscc_xmlv1p1/learning-application-resource') {

                            $href = $resource['@attributes']['href'];
                            $quiz_dirs[] = str_replace('/assessment_meta.xml', '', $href);
                        }
                    }
                    break;
                case('v2.2'):
                    $resources_list = $array['resources']['resource'];
                    $qtiImport->where('user_id', $this->user_id)
                        ->where('directory', $filename_as_dir)
                        ->delete();

                    foreach ($resources_list as $resource) {
                        $filename = $resource['@attributes']['href'];
                        if (!is_file("$unzipped_dir/$filename")) {
                            $this->qtiJob->where('id', $this->qtiJob->id)
                                ->update(['message' => "$filename is in your imsmanifest.xml file but the file does not exist in your zipped folder.", 'status' => 'error']);
                            return false;
                        }
                    }
                    break;
                default:
                    $this->qtiJob->where('id', $this->qtiJob->id)
                        ->update(['message' => "{$this->qtiJob->qti_source} is not a valid QTI source.", 'status' => 'error']);
                    return false;
            }
            $this->qtiJob->where('id', $this->qtiJob->id)
                ->update(['message' => "Saving file information to the database."]);
            DB::beginTransaction();
            switch ($this->qtiJob->qti_source) {
                case('canvas'):
                    foreach ($quiz_dirs as $quiz_dir) {
                        if (strpos(file_get_contents("$unzipped_dir/$quiz_dir/assessment_meta.xml"), 'canvas.instructure.com') === false) {
                            throw new Exception ("This does not look like a Canvas QTI quiz export.");
                        }
                        $assignment = null;
                        if ($this->import_to_course) {
                            $import_to_course = Course::find($this->import_to_course);
                            $assessment_meta = simplexml_load_file("$unzipped_dir/$quiz_dir/assessment_meta.xml");
                            $assignment = DB::table('assignments')->where('name', $assessment_meta->title)
                                ->where('course_id', $this->import_to_course)
                                ->first();
                            if (!$assignment) {
                                $assignment_template = AssignmentTemplate::find($this->assignment_template);

                                $assignment_info = $assignment_template->toArray();
                                $assignment_info['name'] = $assessment_meta->title;
                                $assignment_info['instructions'] = $assessment_meta->description;
                                $assignment_info['course_id'] = $this->import_to_course;
                                $assignment_info['order'] = $import_to_course->assignments->count() + 1;
                                foreach (['id', 'template_name', 'template_description', 'user_id', 'created_at', 'updated_at', 'assign_to_everyone'] as $value) {
                                    unset($assignment_info[$value]);
                                }
                                $assign_tos = Helper::getDefaultAssignTos($this->import_to_course);
                                $assignment = Assignment::create($assignment_info);
                                $this->addAssignTos($assignment, $assign_tos, new Section(), User::find($this->user_id));
                            }
                        }
                        $xml = simplexml_load_file("$unzipped_dir/$quiz_dir/$quiz_dir.xml");
                        $section = $xml->assessment->section;
                        if ($section->section) {
                            $section = $section->section;
                        }
                        foreach ($section->item as $question) {
                            $json = json_encode($question->attributes());
                            $array = json_decode($json, TRUE);
                            $qtiImport = new QtiImport();
                            $identifier = $array['@attributes']['ident'];
                            if ($qti_import = $qtiImport
                                ->where('qti_job_id', $this->qtiJob->id)
                                ->where('identifier', $identifier)
                                ->first()) {
                                $qtiImport = $qti_import;
                                $qtiImport->question_id = null;
                                $qtiImport->status = 'processing';
                            }

                            $qtiImport->assignment_id = $assignment ? $assignment->id : null;
                            $qtiImport->qti_job_id = $this->qtiJob->id;
                            $qtiImport->identifier = $identifier;
                            $qtiImport->xml = $question->asXml();
                            $qtiImport->save();
                        }
                    }
                    break;
                case('v2.2'):

                    foreach ($resources_list as $resource) {
                        $qtiImport = new QtiImport();
                        $filename = $resource['@attributes']['href'];
                        $qtiImport->qti_job_id = $this->qtiJob->id;
                        $qtiImport->identifier = $filename_as_dir;
                        $qtiImport->filename = $filename;
                        $qtiImport->xml = file_get_contents("$unzipped_dir/$filename");
                        $qtiImport->save();
                    }
                    break;
            }
            $this->qtiJob->where('id', $this->qtiJob->id)
                ->update(['status' => 'completed', 'message' => 'Importing individual questions.']);
            DB::commit();
        } catch (Exception $e) {
            if (DB::transactionLevel()) {
                DB::rollback();
            }
            $h = new Handler(app());
            $h->report($e);
            $this->qtiJob->where('id', $this->qtiJob->id)
                ->update(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
