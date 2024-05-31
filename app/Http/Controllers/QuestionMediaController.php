<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\QuestionMediaUpload;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class QuestionMediaController extends Controller
{
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
        try {
            DB::beginTransaction();
            $s3_key = $questionMediaUpload->s3_key;
            $original_filename = $questionMediaUpload->filename;
            $questionMediaUpload->delete();
            $file_name_without_ext = pathinfo($s3_key, PATHINFO_FILENAME);
            $vtt_file = "$file_name_without_ext.vtt";
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

    public function index(string $media, int $start_time = 0)
    {
        $questionMediaUpload = new QuestionMediaUpload();
        $file_name_without_ext = pathinfo($media, PATHINFO_FILENAME);
        $vtt_file = "$file_name_without_ext.vtt";
        $temporary_url = Storage::disk('s3')->temporaryUrl("{$questionMediaUpload->getDir()}/$media", Carbon::now()->addDays(7));
        $vtt_file = Storage::disk('s3')->temporaryUrl("{$questionMediaUpload->getDir()}/$vtt_file", Carbon::now()->addDays(7));
        return view('question_media', ['temporary_url' => $temporary_url, 'vtt_file' => $vtt_file, 'start_time' => $start_time]);
    }

    /**
     * @param Request $request
     * @param QuestionMediaUpload $questionMediaUpload
     * @param int $caption
     * @return array
     * @throws Exception
     */
    public function updateCaption(Request             $request,
                                  QuestionMediaUpload $questionMediaUpload,
                                  int                 $caption): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('updateCaption', $questionMediaUpload);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            DB::beginTransaction();
            $parsed_vtt = $questionMediaUpload->parseVtt($questionMediaUpload->transcript);
            $parsed_vtt[$caption]['text'] = $request->text;
            $questionMediaUpload->transcript = $questionMediaUpload->convertArrayToVTT($parsed_vtt);
            $questionMediaUpload->save();
            $s3_dir = $questionMediaUpload->getDir();
            $s3_key = $questionMediaUpload->s3_key;
            $file_name_without_ext = pathinfo($s3_key, PATHINFO_FILENAME);
            Storage::disk('s3')->put("$s3_dir/$file_name_without_ext.vtt", $questionMediaUpload->transcript);
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
