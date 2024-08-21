<?php

namespace App\Jobs;

use App\DiscussionComment;
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
     * @var string
     */
    private $upload_type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $s3_key, string $upload_type)
    {
        $this->s3_key = $s3_key;
        $this->upload_type = $upload_type;
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

        switch ($this->upload_type) {
            case('question_media_upload'):
                $s3_key_column = 's3_key';
                $uploadTypeModel = new QuestionMediaUpload();
                break;
            case('discussion_comment'):
                $uploadTypeModel = new DiscussionComment();
                $s3_key_column = 'file';
                break;
            default:
                throw new Exception ("Invalid upload type for transcribing: $this->upload_type");
        }
        $upload_type_model = $uploadTypeModel->where($s3_key_column, $this->s3_key)->first();

        $supportedFormats = ['flac', 'm4a', 'mp3', 'mp4', 'mpeg', 'mpga', 'oga', 'ogg', 'wav', 'webm'];
        $fileExtension = pathinfo($this->s3_key, PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileExtension), $supportedFormats)) {
            $upload_type_model->status = "completed";
            $upload_type_model->save();
            exit;
        }

        try {
            $efs_dir ="/mnt/local/";
            $is_efs = is_dir($efs_dir);
            $storage_path = $is_efs
                ? $efs_dir
                : Storage::disk('local')->getAdapter()->getPathPrefix();

            $s3_dir = (new QuestionMediaUpload)->getDir();
            $question_media_dir = $storage_path.$s3_dir;
            $media_upload_path = "$question_media_dir/$this->s3_key";
            if (!is_dir($question_media_dir)) {
                mkdir($question_media_dir);
            }

            $s3_key = "$s3_dir/$this->s3_key";
            if (!Storage::disk('s3')->exists($s3_key)) {
                $message = "$s3_key does not exist.";
                $upload_type_model->status = "error";
                $upload_type_model->message = $message;
                $upload_type_model->save();
                throw new Exception($message);
            }
            $media_content = Storage::disk('s3')->get($s3_key);
            $upload_type_model->status = "getting file";
            $upload_type_model->save();

            file_put_contents($media_upload_path, $media_content);


            $upload_type_model->status = "transcribing";
            $upload_type_model->save();
            $transcript = $this->transcribeWithWhisper($media_upload_path);
            $upload_type_model->status = "saving vtt to database";
            $upload_type_model->transcript = $transcript;
            $upload_type_model->save();
            $file_name_without_ext = pathinfo($s3_key, PATHINFO_FILENAME);
            Storage::disk('s3')->put("$s3_dir/$file_name_without_ext.vtt", $transcript);
            $upload_type_model->status = "completed";
            $upload_type_model->save();
            if ($this->upload_type === 'question_media_upload') {
                $upload_type_model->emailResult('success');
            }
        } catch (Exception $e) {
            $upload_type_model->message = $e->getMessage();
            $upload_type_model->status = "error";
            $upload_type_model->save();
            $h = new Handler(app());
            $h->report($e);
            if ($this->upload_type === 'question_media_upload') {
                $upload_type_model->emailResult('error');
            }
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
