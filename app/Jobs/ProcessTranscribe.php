<?php

namespace App\Jobs;

use App\DiscussionComment;
use App\Exceptions\Handler;
use App\Helpers\Helper;
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
        $file_extension = pathinfo($this->s3_key, PATHINFO_EXTENSION);
        if (!in_array(strtolower($file_extension), $supportedFormats)) {
            $upload_type_model->status = "completed";
            $upload_type_model->save();
            exit;
        }

        try {
            $efs_dir = "/mnt/local/";
            $is_efs = is_dir($efs_dir);
            $storage_path = $is_efs
                ? $efs_dir
                : Storage::disk('local')->getAdapter()->getPathPrefix();

            /*  $storage_path = app()->environment('local')
                  ? app()->storagePath() . "/app/tmp"
                  : '/tmp';*/

            $s3_dir = (new QuestionMediaUpload)->getDir();
            $question_media_dir = $storage_path . $s3_dir;
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
            $adapter = Storage::disk('s3')->getDriver()->getAdapter(); // Get the filesystem adapter
            $client = $adapter->getClient(); // Get the aws client
            $bucket = $adapter->getBucket(); // Get the current bucket
            $client->getObject([
                'Bucket' => $bucket,
                'Key' => $s3_key,
                'SaveAs' => $media_upload_path,
            ]);
            $upload_type_model->status = "getting file";
            $upload_type_model->message = "";
            $upload_type_model->transcript = "";
            $upload_type_model->save();


            $upload_type_model->status = "transcribing";
            $upload_type_model->save();

            $transcript = $this->transcribeWithWhisper($media_upload_path, $s3_key, $upload_type_model);
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
     * @param $s3_key
     * @param $upload_type_model
     * @return string
     * @throws Exception
     */
    function transcribeWithWhisper($media_upload_path, $s3_key, $upload_type_model): string
    {
        $openai = new OpenAi(config('myconfig.openai_api_key'));

        // Split the video into smaller chunks using FFmpeg
        $output_dir = pathinfo($media_upload_path, PATHINFO_DIRNAME);
        $file_extension = pathinfo($s3_key, PATHINFO_EXTENSION);
        $file_extension = $file_extension === 'webm' ? 'mp4' : $file_extension;
        $identifier = pathinfo($s3_key, PATHINFO_FILENAME);
        $output_file_pattern = "$output_dir/$identifier-chunk_%03d.$file_extension";


        // Get the path info
        $path_info = pathinfo($media_upload_path);

        $new_media_upload_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.' . $file_extension;


        if ($file_extension === 'mp4') {
            $new_media_upload_path = $path_info['dirname'] . '/temp-' . $path_info['filename'] . '.' . $file_extension;

            $command = "ffmpeg -i $media_upload_path -c:v libx264 -c:a aac -strict experimental -y $new_media_upload_path";

            list($returnValue, $output, $errorOutput) = Helper::runFfmpegCommand($command);

            if ($returnValue !== 0) {
                throw new Exception ("FFmpeg error processing $s3_key: $errorOutput)");
            }

        }

        $command = "ffmpeg -i $new_media_upload_path -c copy -map 0 -loglevel error -segment_time 30 -f segment $output_file_pattern";

        list($returnValue, $output, $errorOutput) = Helper::runFfmpegCommand($command);

        if ($returnValue !== 0) {
            throw new Exception ("FFmpeg error processing $s3_key: $errorOutput)");
        }


        $transcripts = [];
        $language = null;
        if ($upload_type_model instanceof DiscussionComment) {
            $assignment_question = DB::table('discussion_comments')
                ->join('discussions', 'discussion_comments.discussion_id', '=', 'discussions.id')
                ->join('assignment_question', function ($join) {
                    $join->on('assignment_question.assignment_id', '=', 'discussions.assignment_id')
                        ->on('assignment_question.question_id', '=', 'discussions.question_id');
                })
                ->where('discussion_comments.id', $upload_type_model->id)
                ->select('assignment_question.*')
                ->first();
            if ($assignment_question) {
                $discuss_it_settings = json_decode($assignment_question->discuss_it_settings, 1);
                if (isset($discuss_it_settings['language'])
                    && $discuss_it_settings['language']
                    && $discuss_it_settings['language'] !== 'multiple') {
                    $language = $discuss_it_settings['language'];
                }
            }


        }
        foreach (glob("$output_dir/$identifier-chunk_*.$file_extension") as $key => $chunk) {
            $upload_type_model->message = "Transcribing chunk $key";
            $upload_type_model->save();
            $cFile = curl_file_create($chunk);
            $whisper_config = [
                "model" => "whisper-1",
                "file" => $cFile,
                "response_format" => "vtt"
            ];
            if ($language) {
                $whisper_config['language'] = $language;
            }
            Log::info(json_encode($whisper_config));
            $response = $openai->transcribe($whisper_config);

            $json_response = json_decode($response);
            if ($json_response && isset($json_response->error)) {
                throw new Exception($response);
            }

            $transcripts[] = $response;
            $upload_type_model->message = "Transcribed chunk $key";
            $upload_type_model->save();
        }


        $transcript = $this->mergeVTTChunks($transcripts);

        $upload_type_model->message = "Finished transcription";
        $upload_type_model->save();
        foreach (glob("$output_dir/$identifier-chunk_*.$file_extension") as $chunk) {
            if (file_exists($chunk)) {
                unlink($chunk);
            }
        }
        if (file_exists($new_media_upload_path)) {
            unlink($new_media_upload_path);
        }
        return $transcript;
    }

    function mergeVTTChunks(array $transcripts): string
    {

        $totalTime = 0;
        $finalVttContent = '';
        foreach ($transcripts as $transcript) {
            $lines = explode("\n", $transcript);
            $first_timing = '';
            $current_timing = '';
            $end = 0;

            foreach ($lines as $line) {
                if (preg_match('/(\d{2}):(\d{2}):(\d{2})\.(\d{3}) --> (\d{2}):(\d{2}):(\d{2})\.(\d{3})/', $line, $matches)) {
                    $start = $this->convertToMilliseconds($matches[1], $matches[2], $matches[3], $matches[4]) + $totalTime;
                    $end = $this->convertToMilliseconds($matches[5], $matches[6], $matches[7], $matches[8]) + $totalTime;
                    $adjustedStart = $this->convertToVttTimestamp($start);
                    $adjustedEnd = $this->convertToVttTimestamp($end);
                    $current_timing = "$adjustedStart --> $adjustedEnd\n";
                    if (!$first_timing) {
                        $first_timing = "$current_timing";
                    }
                    $finalVttContent .= "$current_timing";

                } else {
                    $finalVttContent .= $line . "\n";
                }
            }

            // Calculate the total time duration of the current chunk and add to the total time
            if (preg_match('/(\d{2}):(\d{2}):(\d{2})\.(\d{3}) --> (\d{2}):(\d{2}):(\d{2})\.(\d{3})/', $first_timing, $matches)) {
                $start = $this->convertToMilliseconds($matches[1], $matches[2], $matches[3], $matches[4]);
                if (preg_match('/(\d{2}):(\d{2}):(\d{2})\.(\d{3}) --> (\d{2}):(\d{2}):(\d{2})\.(\d{3})/', $current_timing, $matches)) {
                    $end = $this->convertToMilliseconds($matches[5], $matches[6], $matches[7], $matches[8]);
                }
                $totalTime += $end - $start;

            }
        }
        $finalVttContent = str_replace("WEBVTT\n\n", '', $finalVttContent);
        $finalVttContent = str_replace("\n\n\n", "\n\n", $finalVttContent);
        return "WEBVTT\n\n$finalVttContent";
    }

    function convertToMilliseconds($hours, $minutes, $seconds, $milliseconds)
    {
        return ($hours * 3600 + $minutes * 60 + $seconds) * 1000 + $milliseconds;
    }

    function convertToVttTimestamp($milliseconds): string
    {
        $hours = floor($milliseconds / 3600000);
        $milliseconds -= $hours * 3600000;
        $minutes = floor($milliseconds / 60000);
        $milliseconds -= $minutes * 60000;
        $seconds = floor($milliseconds / 1000);
        $milliseconds -= $seconds * 1000;

        return sprintf('%02d:%02d:%02d.%03d', $hours, $minutes, $seconds, $milliseconds);
    }
}
