<?php

namespace App\Http\Controllers;


use App\Assignment;
use App\AssignmentSyncQuestion;
use App\BetaCourseApproval;
use App\Exceptions\Handler;
use App\QtiImport;
use App\QtiJob;
use App\Question;
use App\SavedQuestionsFolder;
use DOMDocument;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class QtiImportController extends Controller
{


    function cleanUp(QtiImport $qtiImport)
    {
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


    }

    function store(Request                $request,
                   QtiImport              $qtiImport,
                   QtiJob                 $qtiJob,
                   Question               $question,
                   AssignmentSyncQuestion $assignmentSyncQuestion): array
    {
        $response['type'] = 'error';
        $qti_job = $qtiJob->where('id', $request->qti_job_id)->first();
        if (!$qti_job) {
            $response['message'] = "The QTI job with ID $request->qti_job_id does not exist in the database.";
            return $response;
        }
        $authorized = Gate::inspect('store', [$qtiImport, $qti_job]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $qti_import = $qtiImport
                ->where('qti_job_id', $request->qti_job_id)
                ->where('identifier', $request->identifier)
                ->first();
            if (!$qti_import) {
                $response['message'] = "The QTI identifier $request->identifier does not exist in the database.";
                return $response;
            }
            $title = 'None provided';
            $already_exists = false;
            if ($xml_exists = $qtiImport
                ->where('xml', $qti_import->xml)
                ->whereNotNull('question_id')
                ->first()) {
                $question = Question::find($xml_exists->question_id);
                $already_exists = $question->id;
            } else {
                $xml = $qtiImport->cleanUpXml($qti_import->xml);

                if (!$xml) {
                    $response['message'] = "$request->identifier does not have valid XML.";
                    return $response;
                }


                $xml_array = json_decode(json_encode($xml), true);
                $title = $xml_array['@attributes']['title'] ?? 'None provided.';
                $htmlDom = new DOMDocument();
                switch ($qti_job->qti_source) {
                    case('canvas'):
                        $question_type = '';
                        foreach ($xml_array['itemmetadata']['qtimetadata']['qtimetadatafield'] as $value) {
                            if ($value['fieldlabel'] === 'question_type') {
                                $question_type = $value['fieldentry'];
                            }
                        }
                        switch ($question_type) {
                            case('matching_question'):
                                $xml_array = $qtiImport->processMatching($qti_import->xml, $xml_array);
                                $xml_array['questionType'] = 'matching';
                                break;
                            case('multiple_choice_question'):
                            case('true_false_question'):
                                $question_type = str_replace('_question', '', $question_type);
                                $xml_array = $qtiImport->processSimpleChoice($xml_array, $question_type);
                                $prompt = $xml_array['prompt'];
                                $xml_array['questionType'] = $question_type;
                                if ($prompt) {
                                    $xml_array['prompt'] = $question->sendImgsToS3($request->user()->id, $qti_job->qti_directory, $prompt, $htmlDom);
                                }
                                break;
                            case('short_answer_question'):
                            case('fill_in_multiple_blanks_question'):
                                $xml_array = ($question_type === 'short_answer_question')
                                    ? $qtiImport->processShortAnswerQuestion($xml_array)
                                    : $qtiImport->processFillInMultipleBlanksQuestion($xml_array);
                                foreach ($xml_array['responseDeclaration']['correctResponse'] as $key => $value) {
                                    $xml_array['responseDeclaration']['correctResponse'][$key]['matchingType'] = 'exact';
                                    $xml_array['responseDeclaration']['correctResponse'][$key]['caseSensitive'] = 'no';
                                }
                                $xml_array['questionType'] = 'fill_in_the_blank';
                                break;
                            case('multiple_dropdowns_question'):
                                $xml_array = $qtiImport->processMultipleDropDowns($xml_array);
                                $xml_array['questionType'] = 'select_choice';

                                preg_match_all('/\[(.*?)\]/', $xml_array['itemBody'], $matches);

                                if (count($matches[0]) !== count($xml_array['responseDeclaration']['correctResponse'])) {
                                    $response['message'] = "The number of correct responses does not equal the number of identifiers. Please be sure that each identifier is unique.<br><br>Question text: {$xml_array['itemBody']}";
                                    //return $response;
                                }
                                break;
                            case('multiple_answers_question'):
                                $xml_array = $qtiImport->processMultipleAnswersQuestion($xml_array);
                                $xml_array['questionType'] = 'multiple_answers';
                                break;
                            default:
                                throw new Exception ("$question_type does not yet exist.");

                        }

                        break;
                    case('v2.2'):
                        if (strpos($qti_import->xml, 'imsqti_v2p2') === false) {
                            $response['message'] = "Currently only QTI version 2.2 is accepted.";
                            return $response;
                        }
                        $simple_choice_array = ["identifier" => "RESPONSE",
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
                            $response['message'] = "$request->identifier is not a simple choice QTI problem.";
                            return $response;
                        }
                        $prompt = $xml_array['itemBody']['prompt'];
                        $like_question_id = $question->qtiSimpleChoiceQuestionExists(json_encode($xml), $prompt, 0);
                        if ($like_question_id) {
                            $response['message'] = "This question is identical to the native question with ADAPT ID $like_question_id.";
                            return $response;
                        }
                        break;
                }



                $question->qti_json = json_encode($xml_array);
                $question->library = 'adapt';
                $question->technology = 'qti';
                $question->title = $title;
                $question->page_id = 0;
                $question->technology_iframe = '';
                $question->author = $qti_job->author;
                $question->public = $qti_job->public;
                $question->folder_id = $qti_job->folder_id;
                $question->question_editor_user_id = $request->user()->id;
                $question->license = $qti_job->license;
                $question->license_version = $qti_job->license_version;
                $question->save();
                $question->page_id = $question->id;
                $question->save();
            }

            $assignment_id = $qti_import->assignment_id;
            if ($assignment_id) {
                $assignment = Assignment::find($assignment_id);
                if (!in_array($question->id, $assignment->questions->pluck('id')->toArray())) {
                    $assignmentSyncQuestion->store($assignment, $question, new BetaCourseApproval());
                };
            }
            $qtiImport->where('id', $qti_import->id)
                ->update(['question_id' => $question->id,
                    'status' => 'success']);
            $response['title'] = $already_exists
                ? "Already exists in the database with ADAPT ID: $question->id."
                : $title;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $qtiImport->where('id', $qti_import->id)
                ->update(['question_id' => $question->id,
                    'status' => $e->getMessage()]);
            $response['message'] = $e->getMessage();
        }

        return $response;
    }
}
