<?php

namespace App\Jobs;

use App\Exceptions\Handler;
use App\QuestionMediaUpload;
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
use Orhanerday\OpenAi\OpenAi;

class ProcessTranscribe implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    private $s3_key;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $s3_key)
    {
        $this->s3_key = $s3_key;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function handle()
    {

        $questionMediaUpload = QuestionMediaUpload::where('s3_key', $this->s3_key)->first();
        $efs_dir = '/mnt/local/';
        $is_efs = is_dir($efs_dir);
        $storage_path = $is_efs
            ? $efs_dir
            : Storage::disk('local')->getAdapter()->getPathPrefix();

        $question_media_dir = $storage_path . $questionMediaUpload->getDir();
        $media_upload_path = "$question_media_dir/$this->s3_key";
        if (!is_dir($question_media_dir)) {
            mkdir($question_media_dir);
        }
        $s3_dir = (new QuestionMediaUpload)->getDir();
        $s3_key = "$s3_dir/$this->s3_key";
        if (!Storage::disk('s3')->exists($s3_key)) {
            $message = "$s3_key does not exist.";
            $questionMediaUpload->status = "error";
            $questionMediaUpload->message = $message;
            $questionMediaUpload->save();
            throw new Exception($message);
        }
        $media_content = Storage::disk('s3')->get($s3_key);
        $questionMediaUpload->status = "getting file";
        $questionMediaUpload->save();
        file_put_contents($media_upload_path, $media_content);

        try {

            $questionMediaUpload->status = "transcribing";
            $questionMediaUpload->save();
            $transcript = $this->transcribeWithWhisper($media_upload_path);
            $questionMediaUpload->status = "saving vtt to database";
            $questionMediaUpload->transcript = $transcript;
            $questionMediaUpload->save();
            $file_name_without_ext = pathinfo($s3_key, PATHINFO_FILENAME);
            Storage::disk('s3')->put("$s3_dir/$file_name_without_ext.vtt", $transcript);
            $questionMediaUpload->status = "completed";
            $questionMediaUpload->save();
            $questionMediaUpload->emailResult('success');
        } catch (Exception $e) {
            $questionMediaUpload->message = $e->getMessage();
            $questionMediaUpload->status = "error";
            $questionMediaUpload->save();
            $h = new Handler(app());
            $h->report($e);
            $questionMediaUpload->emailResult('error');
        }

    }

    /**
     * @param $media_upload_path
     * @return bool|string
     * @throws Exception
     */
    function transcribeWithWhisper($media_upload_path)
    {
        $openai = new OpenAi(config('myconfig.openai_api_key'));
        if (!file_exists($media_upload_path)) {
            throw new Exception("$media_upload_path does not exist.");
        }
        $cFile = curl_file_create($media_upload_path);
        $response = $openai->transcribe([
            "model" => "whisper-1",
            "file" => $cFile,
            "response_format" => "vtt"
        ]);
        $json_response = json_decode($response);
        if ($json_response && $json_response->error) {
            throw new Exception($response);
        }
        return $response;

    }
}
