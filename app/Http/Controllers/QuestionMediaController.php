<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\DiscussionComment;
use App\Exceptions\Handler;
use App\Jobs\ProcessTranscribe;
use App\Question;
use App\QuestionMediaUpload;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionMediaController extends Controller
{
    /**
     * @param Request $request
     * @param QuestionMediaUpload $questionMediaUpload
     * @return array
     * @throws Exception
     */
    public function temporaryUrls(Request $request, QuestionMediaUpload $questionMediaUpload)
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('temporaryUrls', $questionMediaUpload);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $question_media_uploads = $request->question_media_uploads;
            foreach ($question_media_uploads as $key => $question_media_upload) {
                $s3_key = $question_media_upload['s3_key'];
                $question_media_uploads[$key]['temporary_url'] = strpos($s3_key, '.pdf') !== false ?
                    Storage::disk('s3')
                        ->temporaryUrl("{$questionMediaUpload->getDir()}/$s3_key", Carbon::now()->addDays(7))
                    : null;

            }
            $response['type'] = 'success';
            $response['question_media_uploads'] = $question_media_uploads;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We could not get the temporary urls. Please try again or contact us for assistance.";

        }
        return $response;

    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param QuestionMediaUpload $questionMediaUpload
     * @return array
     * @throws Exception
     */
    public function getByQuestion(Assignment          $assignment,
                                  Question            $question,
                                  QuestionMediaUpload $questionMediaUpload)
    {

        try {
//need the assignment ID for authorization
            $response['type'] = 'error';
            $question_media_uploads = $questionMediaUpload
                ->where('question_id', $question->id)
                ->orderBy('order')
                ->get();
            foreach ($question_media_uploads as $key => $value) {
                if (strpos($value->s3_key, '.pdf') !== false) {
                    $question_media_uploads[$key]['temporary_url'] = Storage::disk('s3')->temporaryUrl("{$questionMediaUpload->getDir()}/$value->s3_key", Carbon::now()->addDays(7));

                }
            }
            $response['question_media_uploads'] = $question_media_uploads;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We could not get the media associated with this question. Please try again or contact us for assistance.";

        }
        return $response;


    }

    /**
     * @param string $src
     * @return Application|Factory|View
     */
    public function conductorMedia(string $src)
    {
        return view('conductor_media', ['src' => $src]);
    }


    /**
     * @param QuestionMediaUpload $questionMediaUpload
     * @return array
     * @throws Exception
     */
    public function validateVTT(QuestionMediaUpload $questionMediaUpload): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('validateVTT', $questionMediaUpload);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $s3_key = $questionMediaUpload->s3_key;
            $original_s3_key = $s3_key;
            $vtt_file = $questionMediaUpload->getVttFileNameFromS3Key($s3_key);
            $s3_key = "{$questionMediaUpload->getDir()}/$vtt_file";
            if (!Storage::disk('s3')->exists($s3_key)) {
                $response['message'] = "We were unable to locate the .vtt file on the server.";
                return $response;
            }

            // Check the file extension
            $extension = pathinfo($s3_key, PATHINFO_EXTENSION);
            if (strtolower($extension) !== 'vtt') {
                $response['message'] = "That is not a .vtt file.";
                Storage::disk('s3')->delete($s3_key);
                return $response;
            }

            $vtt_content = Storage::disk('s3')->get($s3_key);
            if (strpos($vtt_content, 'WEBVTT') !== 0) {
                $response['message'] = "Missing WEBVTT from the file.";
                Storage::disk('s3')->delete($s3_key);
                return $response;
            }
            $questionMediaUpload->where('s3_key', $original_s3_key)->update(['transcript' => $vtt_content]);

            $transcript = $questionMediaUpload->parseVtt($vtt_content);


            $response['type'] = 'success';
            $response['transcript'] = $transcript;
            $response['message'] = "The transcript has been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We could not upload the transcript. Please try again or contact us for assistance.";

        }
        return $response;

    }


    /**
     * @param QuestionMediaUpload $questionMediaUpload
     * @return array|StreamedResponse
     * @throws Exception
     */
    public function downloadTranscript(QuestionMediaUpload $questionMediaUpload)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('download', $questionMediaUpload);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $vtt_file = $questionMediaUpload->getVttFileNameFromS3Key();
            if (Storage::disk('s3')->exists("{$questionMediaUpload->getDir()}/$vtt_file")) {
                return Storage::disk('s3')->download("{$questionMediaUpload->getDir()}/$vtt_file");
            } else {
                $response['message'] = "We were unable to locate the .vtt file $vtt_file.";

            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We could not download the .vtt file. Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param QuestionMediaUpload $questionMediaUpload
     * @return array
     * @throws Exception
     */
    public function destroy(QuestionMediaUpload $questionMediaUpload): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('destroy', $questionMediaUpload);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        if (DB::table('discussions')->where('media_upload_id', $questionMediaUpload->id)->first()) {
            $response['message'] = "This media cannot be deleted since it has already been used as part of a discussion in an assignment question.";
            return $response;
        }
        try {
            DB::beginTransaction();
            $original_filename = $questionMediaUpload->original_filename;
            $questionMediaUpload->delete();
            $vtt_file = $questionMediaUpload->getVttFileNameFromS3Key();
            if (Storage::disk('s3')->exists("{$questionMediaUpload->getDir()}/$vtt_file")) {
                Storage::disk('s3')->delete("{$questionMediaUpload->getDir()}/$vtt_file");
            }
            Storage::disk('s3')->delete("{$questionMediaUpload->getDir()}/$questionMediaUpload->s3_key");
            DB::commit();
            $response['type'] = 'info';
            $response['message'] = "$original_filename has been deleted.";
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We could not delete the question media. Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @throws Exception
     */
    public function index(string $media, int $start_time = 0)
    {
        $questionMediaUpload = new QuestionMediaUpload();
        $vtt_file = $questionMediaUpload->getVttFileNameFromS3Key($media);
        $type = strpos($media, '.mp3') !== false ? 'audio' : 'video';
        $temporary_url = Storage::disk('s3')->temporaryUrl("{$questionMediaUpload->getDir()}/$media", Carbon::now()->addDays(7));
        $vtt_file = Storage::disk('s3')->temporaryUrl("{$questionMediaUpload->getDir()}/$vtt_file", Carbon::now()->addDays(7));
        return view('media_player', ['type' => $type, 'temporary_url' => $temporary_url, 'vtt_file' => $vtt_file, 'start_time' => $start_time]);
    }

    /**
     * @param Request $request
     * @param int $media_upload_id
     * @param int $caption
     * @return array
     * @throws Exception
     */
    public function updateCaption(Request $request,
                                  int     $media_upload_id,
                                  int     $caption): array
    {

        try {
            $response['type'] = 'error';
            $questionMediaUpload = new QuestionMediaUpload();
            switch ($request->model) {
                case('QuestionMediaUpload'):
                    $model = QuestionMediaUpload::find($media_upload_id);
                    $s3_key = $model->s3_key;
                    break;
                case('DiscussionComment'):
                    $model = DiscussionComment::find($media_upload_id);
                    $s3_key = $model->file;
                    break;
                default:
                    throw new Exception("$request->model is not a valid model to update a caption.");
            }
            $authorized = Gate::inspect('updateCaption', $model);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            DB::beginTransaction();
            $parsed_vtt = $questionMediaUpload->parseVtt($model->transcript);
            $parsed_vtt[$caption]['text'] = $request->text;
            $model->transcript = $questionMediaUpload->convertArrayToVTT($parsed_vtt);
            $model->save();
            $s3_dir = $questionMediaUpload->getDir();

            $file_name_without_ext = pathinfo($s3_key, PATHINFO_FILENAME);
            Storage::disk('s3')->put("$s3_dir/$file_name_without_ext.vtt", $model->transcript);
            $response['type'] = 'success';
            $response['message'] = "The caption has been updated.";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We could not update the caption. Please try again or contact us for assistance.";

        }

        return $response;
    }
}
