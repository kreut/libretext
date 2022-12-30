<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Webwork;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class WebworkController extends Controller
{

    /**
     * @param Request $request
     * @param Webwork $webwork
     * @return array
     * @throws Exception
     */
    public function uploadAttachment(Request $request, Webwork $webwork): array
    {

        $response['type'] = 'error';
        $session_identifier = $request->session_identifier;
        $authorized = Gate::inspect('uploadAttachment', $webwork);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        if (!$session_identifier) {
            $response['message'] = "Request is missing a session identifier.";
            return $response;
        }
        $validator = Validator::make($request->all(), [
            'file' => 'mimes:jpeg,bmp,png,gif,svg',
        ]);
        $filename = $request->file('file')->getClientOriginalName();
        if ($validator->fails()) {
            $response['message'] = "$filename is not an image.";
            return $response;
        }
        try {
            Storage::disk('s3')->put("pending-attachments/$session_identifier/$filename", $request->file('file')->getContent());
            $response['type'] = 'success';
            $response['attachment']= ['filename' => $filename, 'status' => 'pending'];
        } catch (Exception $e){
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error uploading the attachment.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function convertDefFileToMassWebworkUploadCSV(Request $request)
    {
        $contents = file($request->file);
        foreach ($contents as $line) {
            if (str_starts_with($line, 'source_file')) {
                $pg_file = str_replace('source_file = ', '', $line);
                echo $pg_file;
            }
        }
        exit;
        $contents = file_get_contents('/Users/franciscaparedes/Downloads/setCobleBigIdeasCosmology5.def');
        dd($contents);

    }
}
