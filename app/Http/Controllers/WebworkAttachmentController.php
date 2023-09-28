<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Question;
use App\Webwork;
use App\WebworkAttachment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class WebworkAttachmentController extends Controller
{
    /**
     * @param Request $request
     * @param WebworkAttachment $webworkAttachment
     * @param Webwork $webwork
     * @return array
     * @throws Exception
     */
    public function destroyWebworkAttachmentByQuestion(Request $request, WebworkAttachment $webworkAttachment, Webwork $webwork): array
    {

        $response['type'] = 'error';
        $question_id = $request->question_id;
        $question_revision_id = $request->question_revision_id;
        $filename = $request->webwork_attachment['filename'];
        if ($request->webwork_attachment['status'] !== 'pending') {
            $authorized = Gate::inspect('actOnWebworkAttachmentByQuestion', [$webworkAttachment, Question::find($question_id), 'delete']);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
        }
        try {
            DB::beginTransaction();
            if ($request->webwork_attachment['status'] !== 'pending') {
                $webworkAttachment->where('question_id', $question_id)
                    ->where('filename', $filename)
                    ->where('question_revision_id', $question_revision_id)
                    ->delete();
                try {
                    $webwork_dir = $webwork->getDir($question_id, $question_revision_id);
                    $path_to_file = Helper::getWebworkCodePath() . $webwork_dir . "/$filename";
                    $webwork->deletePath($path_to_file);
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Path does not exist') === false) {
                        throw new Exception ($e->getMessage());
                    }
                }
            }
            DB::commit();
            $response['message'] = "$filename has been deleted.  Please update your weBWork code to reflect this change.";
            $response['type'] = 'info';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting the attachment.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * /**
     * @param Question $question
     * @param int $question_revision_id
     * @param WebworkAttachment $webworkAttachment
     * @return array
     * @throws Exception
     */
    public function getWebworkAttachmentsByQuestion(Question          $question,
                                                    int               $question_revision_id,
                                                    WebworkAttachment $webworkAttachment): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('actOnWebworkAttachmentByQuestion', [$webworkAttachment, $question, 'get']);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $webwork_attachments = $webworkAttachment->where('question_id', $question->id)
                ->where('question_revision_id', $question_revision_id)
                ->get();

            foreach ($webwork_attachments as $key => $webwork_attachment) {
                $webwork_attachments[$key]['status'] = 'attached';

            }
            $response['webwork_attachments'] = $webwork_attachments;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the attachments.  Please try again or contact us for assistance.";

        }
        return $response;

    }


    /**
     * @param Request $request
     * @param WebworkAttachment $webworkAttachment
     * @return array
     * @throws Exception
     */
    public function upload(Request $request, WebworkAttachment $webworkAttachment): array
    {

        $response['type'] = 'error';
        $session_identifier = $request->session_identifier;
        $authorized = Gate::inspect('upload', $webworkAttachment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        if (!$session_identifier) {
            $response['message'] = "Request is missing a session identifier.";
            return $response;
        }
        $validator = Validator::make($request->all(), [
            'file' => 'mimes:jpeg,bmp,png,gif,svg,webp',
        ]);
        $file = $request->file('file')->getClientOriginalName();
        $filename = pathinfo($file, PATHINFO_FILENAME);
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $filename = preg_replace('/[^a-z0-9]+/', '_', strtolower($filename)) . "." . $extension;
        if ($validator->fails()) {
            $response['message'] = "$filename is not an image.";
            return $response;
        }
        try {
            $efs_dir = '/mnt/local/';
            $is_efs = is_dir($efs_dir);
            $storage_path = $is_efs
                ? $efs_dir
                : Storage::disk('local')->getAdapter()->getPathPrefix();

            if (!is_dir("{$storage_path}pending-attachments")) {
                mkdir("{$storage_path}pending-attachments");
            }

            if (!is_dir("{$storage_path}pending-attachments/$session_identifier")) {
                mkdir("{$storage_path}pending-attachments/$session_identifier");
            }
            file_put_contents("{$storage_path}pending-attachments/$session_identifier/$filename",
                $request->file('file')->getContent()
            );

            $image_size = getimagesize("{$storage_path}pending-attachments/$session_identifier/$filename");
            $width = $image_size[0];
            $height = $image_size[1];
            $response['type'] = 'success';
            $response['attachment'] = [
                'filename' => $filename,
                'status' => 'pending',
                'width' => $width,
                'height' => $height
            ];
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error uploading the attachment.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}
