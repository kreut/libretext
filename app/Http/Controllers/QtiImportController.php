<?php

namespace App\Http\Controllers;


use App\Exceptions\Handler;
use App\QtiImport;
use App\Question;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QtiImportController extends Controller
{

    function store(Request $request, QtiImport $qtiImport, Question $question): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $qtiImport);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $qti_import = $qtiImport
                ->where('directory', $request->directory)
                ->where('filename', $request->filename)
                ->where('user_id', $request->user()->id)
                ->first();
            if (!$qti_import) {
                $response['message'] = "$request->filename does not exist in the database.";
                return $response;
            }
            //dd($qti_import->xml);
            $xml = $qtiImport->cleanUpXml($qti_import->xml);

            if (!$xml) {
                $response['message'] = "$request->filename does not have valid XML.";
                return $response;
            }
            $xml_array = json_decode(json_encode($xml),true);
            $question->json = json_encode($xml);
            $question->library = 'adapt';
            $question->technology = 'adapt';
            $question->title = $xml_array['@attributes']['title'] ?? null;
            $question->page_id = 0;
            $question->technology_iframe = '';
             $question->save();
            $question->page_id = $question->id;
            $question->save();

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to import this QTI question.  Please try again or contact us for assistance.";
        }

        return $response;
    }
}
