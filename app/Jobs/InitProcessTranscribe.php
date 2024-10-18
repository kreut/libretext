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
use GuzzleHttp\Exception\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Generator;

class InitProcessTranscribe implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * @var string
     */
    private $upload_type;
    /**
     * @var string
     */
    private $filename;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $filename, string $upload_type)
    {
        $this->filename = $filename;
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
        $upload_type_model = (new QuestionMediaUpload())->getUploadTypeModel($this->upload_type,$this->filename);

        $supportedFormats = ['flac', 'm4a', 'mp3', 'mp4', 'mpeg', 'mpga', 'oga', 'ogg', 'wav', 'webm'];
        $file_extension = pathinfo($this->filename, PATHINFO_EXTENSION);
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


            $s3_dir = (new QuestionMediaUpload)->getDir();
            $question_media_dir = $storage_path . $s3_dir;
            $media_upload_path = "$question_media_dir/$this->filename";

            if (!is_dir($question_media_dir)) {
                mkdir($question_media_dir);
            }

            $s3_key = "$s3_dir/$this->filename";
            if (!Storage::disk('s3')->exists($s3_key)) {
                $message = "$s3_key does not exist.";
                $upload_type_model->status = "error";
                $upload_type_model->message = $message;
                $upload_type_model->save();
                throw new Exception($message);
            }


            $upload_type_model->status = "sending to be processed";
            $upload_type_model->save();

            $language = '';
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

            $key_secret = DB::table('key_secrets')->where('key', 'adapt_transcribe')->first();
            if (!$key_secret) {
                throw new Exception("No key_secret for adapt_transcribe exists.");
            }

            $hmac_key = new HmacKey($key_secret->secret);
            $signer = new HS256($hmac_key);
            $generator = new Generator($signer);

            $jwt = $generator->generate(['transcribe' => 1]);


            $response = Http::withToken($jwt) // Add the Bearer token here
            ->timeout(360) // Set the timeout
            ->withHeaders([
                'Content-Type' => 'application/json', // Set the content type to JSON
            ])
                ->post("https://dev.adapt.libretexts.org/api/question-media/init-transcribe", [
                    'upload_type' => $this->upload_type,
                    'filename' => basename($media_upload_path),
                    'language' => $language,
                    'environment' => app()->environment(),
                ]);

            if (!$response->successful()) {
                throw new Exception("Request failed with status: " . $response->status() . " and message: " . $response->body());
            }

            $upload_type_model->status = $response->status();
            $upload_type_model->message =  $response->body();
            $upload_type_model->save();

        } catch (RequestException $e) {
            $h = new Handler(app());
            $h->report($e);
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $message = $response->getBody(); // This will display the full response body
            } else {
                $message = $e->getMessage();
            }
            $upload_type_model->message = $message;
            $upload_type_model->status = "error";
            $upload_type_model->save();

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);

            $upload_type_model->message = $e->getMessage();
            $upload_type_model->status = "error";
            $upload_type_model->save();
        }

    }

}
