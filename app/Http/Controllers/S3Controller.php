<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\PreSignedURL;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class S3Controller extends Controller
{
    public function preSignedURL(Request $request, PreSignedURL $preSignedURL)
    {

        $response['type'] = 'error';
        try {
            $assignment = Assignment::find($request->assignment_id);
            $upload_file_type = $request->upload_file_type;

            $authorized = $upload_file_type === 'qti'
            ? Gate::inspect('qtiPreSignedURL',  $preSignedURL)
            : Gate::inspect('preSignedURL',  [$preSignedURL, $assignment,  $upload_file_type ]);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            $adapter = Storage::disk('s3')->getDriver()->getAdapter(); // Get the filesystem adapter
            $client = $adapter->getClient(); // Get the aws client
            $bucket = $adapter->getBucket(); // Get the current bucket
// Make a PutObject command
            $dir = false;
            switch ( $upload_file_type ) {
                case('submission'):
                    $dir = 'assignments/' . $request->assignment_id;
                    break;
                case('solution'):
                    $dir = 'solutions/' . $request->user()->id;
                    break;
                case('qti'):
                    $dir = 'uploads/qti/'. $request->user()->id;
                    break;

            }
            if (!$dir) {
                throw new Exception("This is not a valid upload file type.");
            }

            $uploaded_filename = md5(uniqid('', true)) . '.' . pathinfo($request->file_name, PATHINFO_EXTENSION);
            $key = "$dir/$uploaded_filename";
            $cmd = $client->getCommand('PutObject', [
                'Bucket' => $bucket,
                'Key' => $key
            ]);

            $response['preSignedURL'] = (string)$client->createPresignedRequest($cmd, '+300 seconds')->getUri();
            if ($upload_file_type === 'qti'){
                $response['qti_file'] = $uploaded_filename;
            } else {
                $response['submission'] = $uploaded_filename;
            }
            $response['s3_key'] = $key;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to get permission for this upload.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}
