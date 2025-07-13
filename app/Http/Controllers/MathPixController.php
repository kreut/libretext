<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\MathPix;
use App\Structure;
use App\Submission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MathPixController extends Controller
{
    /**
     * @param Request $request
     * @param MathPix $mathPix
     * @return array
     * @throws Exception
     */
    public function temporaryUrl(Request $request, MathPix $mathPix): array
    {

        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('temporaryUrl', [$mathPix, $request->user_id, $request->assignment_id, $request->question_id]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $submission_info = Submission::where('user_id', $request->user_id)
                ->where('assignment_id', $request->assignment_id)
                ->where('question_id', $request->question_id)
                ->first();
            $student_response = json_decode($submission_info->submission)->student_response;
            $s3_key = json_decode($student_response)->structure_s3_key;
            $response['temporary_url'] = Storage::disk('s3')->temporaryUrl($s3_key, now()->addMinutes(2));
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get the temporary URL for this image. Please try again and if the problem persists, contact us.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param MathPix $mathPix
     * @return array
     * @throws Exception
     */
    public function convertToSmiles(Request $request, MathPix $mathPix): array
    {
        try {
            $response['type'] = 'error';
            if ($request->user()->role === 3) {
                $authorized = Gate::inspect('convertToSmiles', [$mathPix, $request->user_id, $request->assignment_id, $request->question_id]);
                if (!$authorized->allowed()) {
                    $response['message'] = $authorized->message();
                    return $response;
                }
            }

            $src = Storage::disk('s3')->temporaryUrl($request->s3_key, now()->addMinutes(2));
            $payload = [
                "src" => $src,
                "include_smiles" => true,
            ];

            $key_secret = DB::table('key_secrets')->where('key', 'LIKE', 'mathpix%')->first();

            $mathpix_response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'app_id' => str_replace('mathpix', '', $key_secret->key),
                'app_key' => $key_secret->secret,
            ])->post('https://api.mathpix.com/v3/text', $payload);

            if ($mathpix_response->serverError()) {
                throw new Exception('MathPix server error: ' . $mathpix_response->body());
            }

            if ($mathpix_response->clientError()) {
                throw new Exception('MathPix client error: ' . $mathpix_response->body());
            }


            if ($mathpix_response->successful()) {
                $response['smiles'] = str_replace(['<smiles>', '</smiles>'], '', $mathpix_response['text']) ?? null;
                if (!$response['smiles']) {
                    $response['message'] = 'No SMILES were returned.';
                    $response['raw'] = $mathpix_response->json();
                } else {
                    $response['type'] = 'success';
                }
            } else {
                $response['message'] = $mathpix_response->body();
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to convert the image to SMILES. Please try again and if the problem persists, contact us.";
        }
        return $response;

    }
}
