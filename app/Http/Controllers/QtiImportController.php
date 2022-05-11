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

    function cleanUp(QtiImport $qtiImport){
        $xml = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<!-- Template for choice interaction item -->
<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p2  http://www.imsglobal.org/xsd/qti/qtiv2p2/imsqti_v2p2.xsd" identifier="proola.org/items/2617" title="Long Run Economic Growth 11     Utilize the aggregate production function to distinguish between different sources of labor productivity gro" adaptive="false" timeDependent="false">
  <responseDeclaration identifier="RESPONSE" cardinality="single" baseType="identifier">
    <correctResponse>
        <value>ChoiceC</value></correctResponse>
  </responseDeclaration>
  <outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float">
    <defaultValue>
      <value>0</value>
    </defaultValue>
  </outcomeDeclaration>
  <itemBody>
    <prompt>
      <p>Based on the following graph, determine the stage of production at which productivity of labor is decreasing and efficiency is lost.​<span class="redactor-invisible-space">​<br/><img src="images/image-2617.image" alt="" data-verified="redactor"/></span></p>
    </prompt>
    <choiceInteraction responseIdentifier="RESPONSE" shuffle="false" maxChoices="1">
        <simpleChoice identifier="ChoiceA">Stage 1 (0-L1)​<span class="redactor-invisible-space">​</span><br/></simpleChoice><simpleChoice identifier="ChoiceB">Stage 2 (L1-L2)<span class="redactor-invisible-space">​</span><br/></simpleChoice><simpleChoice identifier="ChoiceC">Stage 3 (L2-L3)<span class="redactor-invisible-space">​</span><br/></simpleChoice></choiceInteraction>
  </itemBody>
  <responseProcessing template="http://www.imsglobal.org/question/qti_v2p2/rptemplates/match_correct"/>
</assessmentItem>
EOD;
       $xml = $qtiImport->cleanUpXml($xml);
       dd(json_decode(json_encode($xml), true));


    }
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
            $title = $xml_array['@attributes']['title'] ?? null;
           $question->qti_json = json_encode($xml_array);
            $question->library = 'adapt';
            $question->technology = 'qti';
            $question->title = $title;
            $question->page_id = 0;
            $question->technology_iframe = '';
            $question->author = $request->author;
            $question->folder_id = $request->folder_id;
            $question->question_editor_user_id = $request->user()->id;
            $question->public = 0;
            $question->license = $request->license;
            $question->license_version = $request->license_version;
            $question->save();
            $question->page_id = $question->id;
            $question->save();

            $qtiImport->where('id', $qti_import->id)
                ->update(['question_id' => $question->id,
                    'status' => 'success']);
            $response['title'] = $title ?: 'None provided';
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $qtiImport->where('id', $qti_import->id)
                ->update(['question_id' => $question->id,
                    'status' =>  $e->getMessage()]);
            $response['message'] = $e->getMessage();
        }

        return $response;
    }
}
