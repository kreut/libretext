<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\PreSignedURL;
use App\QuestionMediaUpload;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class S3Controller extends Controller
{
    /**
     * @param Request $request
     * @param PreSignedURL $preSignedURL
     * @return array
     * @throws Exception
     */
    public function preSignedURL(Request $request, PreSignedURL $preSignedURL): array
    {

        $response['type'] = 'error';
        try {
            $assignment = Assignment::find($request->assignment_id);
            $upload_file_type = $request->upload_file_type;
            switch ($upload_file_type) {
                case('qti'):
                    $authorized = Gate::inspect('qtiPreSignedURL', $preSignedURL);
                    break;
                case('question-media'):
                    $authorized = Gate::inspect('questionMediaPreSignedURL', $preSignedURL);
                    break;
                case('vtt'):
                    $authorized = Gate::inspect('vttPreSignedURL', [$preSignedURL, $request->s3_key]);
                    break;
                default:
                    $authorized = Gate::inspect('preSignedURL', [$preSignedURL, $assignment, $upload_file_type]);

            }

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            $adapter = Storage::disk('s3')->getDriver()->getAdapter(); // Get the filesystem adapter
            $client = $adapter->getClient(); // Get the aws client
            $bucket = $adapter->getBucket(); // Get the current bucket
// Make a PutObject command
            $dir = false;
            $questionMediaUpload = new QuestionMediaUpload();
            switch ($upload_file_type) {
                case('submission'):
                    $dir = 'assignments/' . $request->assignment_id;
                    break;
                case('solution'):
                    $dir = 'solutions/' . $request->user()->id;
                    break;
                case('qti'):
                    $dir = 'uploads/qti/' . $request->user()->id;
                    break;
                case('question-media'):
                case('vtt'):
                    $dir = $questionMediaUpload->getDir();
                    break;

            }
            if (!$dir) {
                throw new Exception("This is not a valid upload file type.");
            }

            $uploaded_filename = $upload_file_type === 'vtt' ?
                $questionMediaUpload->getVttFileNameFromS3Key($request->s3_key)
                : md5(uniqid('', true)) . '.' . pathinfo($request->file_name, PATHINFO_EXTENSION);
            $key = "$dir/$uploaded_filename";
            $cmd = $client->getCommand('PutObject', [
                'Bucket' => $bucket,
                'Key' => $key
            ]);

            $response['preSignedURL'] = (string)$client->createPresignedRequest($cmd, '+300 seconds')->getUri();
            switch ($upload_file_type) {
                case('qti'):
                    $response['qti_file'] = $uploaded_filename;
                    break;
                case('question-media'):
                    $response['question_media_filename'] = $uploaded_filename;
                    break;
                default:
                    $response['submission'] = $uploaded_filename;

            }

            $response['s3_key'] = $key;
            $response['temporary_url'] = Storage::disk('s3')->temporaryUrl($key, now()->addMinutes(360));
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to get permission for this upload.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}
