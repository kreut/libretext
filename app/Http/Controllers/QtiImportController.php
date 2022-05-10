<?php

namespace App\Http\Controllers;


use App\Exceptions\Handler;
use App\QtiImport;
use App\Question;
use App\SavedQuestionsFolder;
use DOMDocument;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QtiImportController extends Controller
{

    function store(Request              $request,
                   QtiImport            $qtiImport,
                   Question             $question,
                   SavedQuestionsFolder $savedQuestionsFolder): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $qtiImport);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        if (!$savedQuestionsFolder->isOwner($request->folder_id)) {
            $response['message'] = "That is not your folder.";
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
            $xml_array = json_decode(json_encode($xml), true);
            $simple_choice_array = [
                "identifier" => "RESPONSE",
                "cardinality" => "single",
                "baseType" => "identifier"];
            $simple_choice = true;
            foreach ($simple_choice_array as $key => $value) {
                if (!isset($xml_array['responseDeclaration']['@attributes'][$key])
                    || $xml_array['responseDeclaration']['@attributes'][$key] !== $value) {
                    $simple_choice = false;
                }
            }
            if (!$simple_choice) {
                $response['message'] = "$request->filename is not a simple choice QTI problem.";
                return $response;
            }

            $htmlDom = new DOMDocument();
            $xml_array['itemBody']['prompt'] = $question->sendImgsToS3($request->user()->id, $request->directory, $xml_array['itemBody']['prompt'], $htmlDom);
            $question->qti_json = json_encode($xml_array);
            $question->library = 'adapt';
            $question->technology = 'qti';
            $question->title = $xml_array['@attributes']['title'] ?? null;
            $question->page_id = 0;
            $question->technology_iframe = '';
            $question->author = $request->author;
            $question->folder_id = $request->folder_id;
            $question->question_editor_user_id = $request->user()->id;
            $question->public = 0;
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
