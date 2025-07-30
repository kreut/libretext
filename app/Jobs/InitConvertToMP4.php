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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Generator;

class InitConvertToMP4 implements ShouldQueue
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
     * @var int
     */
    private $assignment_id;
    /**
     * @var int
     */
    private $discussion_comment_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $discussion_comment_id,
                                int    $assignment_id)
    {
        $this->discussion_comment_id = $discussion_comment_id;
        $this->assignment_id = $assignment_id;
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
        try {
            $discussionComment = DiscussionComment::find($this->discussion_comment_id);
            $filename = $discussionComment->file;
            $questionMediaUpload = new QuestionMediaUpload();
            $question_media_dir = $questionMediaUpload->getDir();
            $s3_key = $question_media_dir . $this->filename;

            if (!Storage::disk('s3')->exists($s3_key)) {
                $message = "$s3_key does not exist when converting to MP4.";
                throw new Exception($message);
            }

            $discussionComment->mp4_status = "sending to be processed";
            $discussionComment->save();


            $key_secret = DB::table('key_secrets')->where('key', 'adapt_transcribe')->first();
            if (!$key_secret) {
                throw new Exception("No key_secret for adapt_transcribe exists.");
            }

            $hmac_key = new HmacKey($key_secret->secret);
            $signer = new HS256($hmac_key);
            $generator = new Generator($signer);

            $jwt = $generator->generate(['convert_to_mp4' => 1]);


            $response = Http::withToken($jwt) // Add the Bearer token here
            ->timeout(360) // Set the timeout
            ->withHeaders([
                'Content-Type' => 'application/json', // Set the content type to JSON
            ])
                ->post("https://dev.adapt.libretexts.org/api/discussion-comment/process-convert-to-mp4", [
                    'filename' => $filename,
                    'environment' => app()->environment(),
                ]);

            if (!$response->successful()) {
                throw new Exception("Request failed with status: " . $response->status() . " and message: " . $response->body());
            }

        } catch (RequestException $e) {
            $h = new Handler(app());
            $h->report($e);
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $message = $response->getBody(); // This will display the full response body
            } else {
                $message = $e->getMessage();
            }
            $discussionComment = DiscussionComment::find($this->discussion_comment_id);
            $discussionComment->mp4_message = $message;
            $discussionComment->mp4_status = "error";
            $discussionComment->save();

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $discussionComment = DiscussionComment::find($this->discussion_comment_id);
            $discussionComment->mp4_message = $e->getMessage();
            $discussionComment->mp4_status= "error";
            $discussionComment->save();
        }

    }

}
