<?php

namespace App\Http\Controllers;

use App\CKEditor;
use App\Exceptions\Handler;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class CKEditorController extends Controller
{
    public function upload(Request $request, CKEditor $CKEditor)
    {
        $CKEditorFuncNum = $request->input('CKEditorFuncNum')
            ? $request->input('CKEditorFuncNum')
            : 0;
        try {
            $authorized = Gate::inspect('upload', $CKEditor);
            if (!$authorized->allowed()) {
                $message = $authorized->message();
                $response = $this->_formatCkeditorTextResponse($CKEditorFuncNum, $message);
                return response($response, 200)
                    ->header('Content-Type', 'text/html');
            }
            if ($request->hasFile('upload')) {
                if (!in_array($request->file('upload')->getMimeType(), ['image/jpeg', 'image/png', 'image/tiff'])) {
                    $message = $request->file('upload')->getMimeType() . " is not a valid mimetype.  Please verify that you are uploading an image.";
                    $response = $this->_formatCkeditorTextResponse($CKEditorFuncNum, $message);
                    return response($response, 200)
                        ->header('Content-Type', 'text/html');

                }
                $extension = $request->file('upload')->getClientOriginalExtension();
                $fileName = uniqid() .  time() . '.' . $extension;
                $img_file = $request->file('upload')->store("uploads/images", 'local');

                $contents = Storage::disk('local')->get($img_file);
                $s3_file = "uploads/images/$fileName";
                Storage::disk('s3')->put($s3_file, $contents);
                //unlink($img_file);
                $url = Storage::disk('s3')->temporaryUrl($s3_file, Carbon::now()->addMinutes(1));
                $response = "<script>window.parent.CKEDITOR.tools.callFunction($CKEditorFuncNum, '$url')</script>";

                $drag_and_drop = strpos($request->getRequestUri(), 'upload&responseType=json') !== false;
                return $drag_and_drop
                    ? response()->json([
                        'uploaded' => '1',
                        'fileName' => $fileName,
                        'url' => $url
                    ])
                    : response($response, 200)
                        ->header('Content-Type', 'text/html');
            } else {
                $response = $this->_formatCkeditorTextResponse($CKEditorFuncNum, 'No upload present.  Please try again or contact us.');
                return response($response, 200)
                    ->header('Content-Type', 'text/html');
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response = $this->_formatCkeditorTextResponse($CKEditorFuncNum, 'Error uploading image.  Please try again or contact us.');

            return response($response, 200)
                ->header('Content-Type', 'text/html');
        }
    }

    private
    function _formatCkeditorTextResponse($CKEditorFuncNum, $message): string
    {
        return "<script>window.parent.CKEDITOR.tools.callFunction($CKEditorFuncNum, '','$message')</script>";
    }

}
