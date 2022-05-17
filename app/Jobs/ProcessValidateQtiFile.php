<?php

namespace App\Jobs;

use App\Exceptions\Handler;
use App\QtiImport;
use App\QtiJob;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ProcessValidateQtiFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $resources_list;
    private $user_id;
    private $filename_as_dir;
    private $unzipped_dir;
    private $qti_file;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($qti_file, $user_id)
    {
        $this->qti_file = $qti_file;
        $this->user_id = $user_id;
    }

    /**
     * @return false|void
     * @throws FileNotFoundException|Exception
     */
    public function handle()
    {
        $qtiImport = new QtiImport();
        $qtiJob = new QtiJob();
        $qtiJob->status = 'processing';
        $qtiJob->qti_directory = pathinfo($this->qti_file)['filename'];
        $qtiJob->user_id = $this->user_id;
        $qtiJob->save();
        $qti_job_id = $qtiJob->id;
        try {
            $dir = "uploads/qti/$this->user_id";
            $path_to_qti_zip = "$dir/$this->qti_file";
            if (!$this->qti_file || !Storage::disk('s3')->exists($path_to_qti_zip)) {
                $qtiJob->where('id', $qti_job_id)
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

            $qtiJob->where('id', $qti_job_id)
                ->update(['message' => "Retrieving the file from the server."]);
            file_put_contents("$storage_path$path_to_qti_zip", Storage::disk('s3')->get($path_to_qti_zip));

            $qtiJob->where('id', $qti_job_id)
                ->update(['message' => "Unzipping the file."]);

            $zip = new ZipArchive();
            $res = $zip->open("$storage_path$path_to_qti_zip");
            if ($res === TRUE) {
                // extract it to the path we determined above
                $filename_as_dir = pathinfo($path_to_qti_zip)['filename'];
                $unzipped_dir = "$local_dir/$filename_as_dir";
                if (!is_dir($unzipped_dir)) {
                    mkdir($unzipped_dir);
                }
                $zip->extractTo($unzipped_dir);
                $zip->close();
                if (!file_exists("$unzipped_dir/imsmanifest.xml")) {
                    $qtiJob->where('id', $qti_job_id)
                        ->update(['message' => 'No imsmanifest.xml is present.', 'status' => 'error']);
                    return false;
                }
                $xml = simplexml_load_file("$unzipped_dir/imsmanifest.xml");
                $json = json_encode($xml);
                $array = json_decode($json, TRUE);
                $resources_list = $array['resources']['resource'];
                $qtiImport->where('user_id', $this->user_id)
                    ->where('directory', $filename_as_dir)
                    ->delete();

                foreach ($resources_list as $resource) {
                    $filename = $resource['@attributes']['href'];
                    if (!is_file("$unzipped_dir/$filename")) {
                        $qtiJob->where('id', $qti_job_id)
                            ->update(['message' => "$filename is in your imsmanifest.xml file but the file does not exist in your zipped folder.", 'status' => 'error']);
                        return false;
                    }
                }

                $qtiJob->where('id', $qti_job_id)
                    ->update(['message' => "Saving file information to the database."]);

                foreach ($resources_list as $resource) {
                    $qtiImport = new QtiImport();
                    $filename = $resource['@attributes']['href'];
                    $qtiImport->user_id = $this->user_id;
                    $qtiImport->directory = $filename_as_dir;
                    $qtiImport->filename = $filename;
                    $qtiImport->xml = file_get_contents("$unzipped_dir/$filename");
                    $qtiImport->save();
                }

                $qtiJob->where('id', $qti_job_id)
                    ->update(['status' => 'completed', 'message' => 'Importing individual questions.']);
            } else {
                $qtiJob->where('id', $qti_job_id)
                    ->update(['status' => 'error', 'message' => 'We were not able to unzip your file.']);
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $qtiJob->where('id', $qti_job_id)
                ->update(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
