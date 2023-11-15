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
<item ident="g08c307f33e0891d9ac9f011416877246" title="Ex 4.1b - balancing">
          <itemmetadata>
            <qtimetadata>
              <qtimetadatafield>
                <fieldlabel>question_type</fieldlabel>
                <fieldentry>fill_in_multiple_blanks_question</fieldentry>
              </qtimetadatafield>
              <qtimetadatafield>
                <fieldlabel>points_possible</fieldlabel>
                <fieldentry>0.5</fieldentry>
              </qtimetadatafield>
              <qtimetadatafield>
                <fieldlabel>original_answer_ids</fieldlabel>
                <fieldentry>6026,6906,5319,2320</fieldentry>
              </qtimetadatafield>
              <qtimetadatafield>
                <fieldlabel>assessment_question_identifierref</fieldlabel>
                <fieldentry>gad07ae8dfef9e361582b0b7aaa3fff38</fieldentry>
              </qtimetadatafield>
            </qtimetadata>
          </itemmetadata>
          <presentation>
            <material>
              <mattext texttype="text/html">&lt;div&gt;
&lt;p&gt;&lt;span&gt;Balance the following reactions by typing the correct coefficient in each box. (If the coefficient is 1, type 1).&lt;/span&gt;&lt;/p&gt;
&lt;p&gt;&amp;nbsp;&lt;/p&gt;
&lt;p&gt;&lt;span&gt;[HC2H3O2] HC&lt;sub&gt;2&lt;/sub&gt;H&lt;sub&gt;3&lt;/sub&gt;O&lt;sub&gt;2&lt;/sub&gt; (aq) + [CaOH2] Ca(OH)&lt;sub&gt;2&lt;/sub&gt; (aq) --&amp;gt; [CaOAc] Ca(C&lt;sub&gt;2&lt;/sub&gt;H&lt;sub&gt;3&lt;/sub&gt;O&lt;sub&gt;2&lt;/sub&gt;)&lt;sub&gt;2&lt;/sub&gt; (aq) + [H2O] H&lt;sub&gt;2&lt;/sub&gt;O (l)&lt;/span&gt;&lt;/p&gt;
&lt;p&gt;&amp;nbsp;&lt;/p&gt;
&lt;/div&gt;</mattext>
            </material>
            <response_lid ident="response_HC2H3O2">
              <material>
                <mattext>HC2H3O2</mattext>
              </material>
              <render_choice>
                <response_label ident="6026">
                  <material>
                    <mattext texttype="text/plain">2</mattext>
                  </material>
                </response_label>
              </render_choice>
            </response_lid>
            <response_lid ident="response_CaOH2">
              <material>
                <mattext>CaOH2</mattext>
              </material>
              <render_choice>
                <response_label ident="6906">
                  <material>
                    <mattext texttype="text/plain">1</mattext>
                  </material>
                </response_label>
              </render_choice>
            </response_lid>
            <response_lid ident="response_CaOAc">
              <material>
                <mattext>CaOAc</mattext>
              </material>
              <render_choice>
                <response_label ident="5319">
                  <material>
                    <mattext texttype="text/plain">1</mattext>
                  </material>
                </response_label>
              </render_choice>
            </response_lid>
            <response_lid ident="response_H2O">
              <material>
                <mattext>H2O</mattext>
              </material>
              <render_choice>
                <response_label ident="2320">
                  <material>
                    <mattext texttype="text/plain">2</mattext>
                  </material>
                </response_label>
              </render_choice>
            </response_lid>
          </presentation>
          <resprocessing>
            <outcomes>
              <decvar maxvalue="100" minvalue="0" varname="SCORE" vartype="Decimal"/>
            </outcomes>
            <respcondition>
              <conditionvar>
                <varequal respident="response_HC2H3O2">6026</varequal>
              </conditionvar>
              <setvar varname="SCORE" action="Add">25.00</setvar>
            </respcondition>
            <respcondition>
              <conditionvar>
                <varequal respident="response_CaOH2">6906</varequal>
              </conditionvar>
              <setvar varname="SCORE" action="Add">25.00</setvar>
            </respcondition>
            <respcondition>
              <conditionvar>
                <varequal respident="response_CaOAc">5319</varequal>
              </conditionvar>
              <setvar varname="SCORE" action="Add">25.00</setvar>
            </respcondition>
            <respcondition>
              <conditionvar>
                <varequal respident="response_H2O">2320</varequal>
              </conditionvar>
              <setvar varname="SCORE" action="Add">25.00</setvar>
            </respcondition>
          </resprocessing>
          <itemfeedback ident="correct_fb">
            <flow_mat>
              <material>
                <mattext texttype="text/html">&lt;p&gt;correct!&amp;nbsp;&lt;/p&gt;
&lt;p&gt;&amp;nbsp;&lt;/p&gt;</mattext>
              </material>
            </flow_mat>
          </itemfeedback>
          <itemfeedback ident="general_incorrect_fb">
            <flow_mat>
              <material>
                <mattext texttype="text/html">&lt;p&gt;Not quite.&lt;/p&gt;
&lt;p&gt;To balance, split the compounds into species. Treat water as H&lt;sup&gt;+&lt;/sup&gt;-OH&lt;sup&gt;-&lt;/sup&gt;:&lt;/p&gt;
&lt;table style="border-collapse: collapse; width: 34.6414%; height: 135px;" border="1"&gt;
&lt;tbody&gt;
&lt;tr style="height: 27px;"&gt;
&lt;td style="width: 33.3333%; height: 27px;"&gt;Species&lt;/td&gt;
&lt;td style="width: 33.3333%; height: 27px;"&gt;Reactant&lt;/td&gt;
&lt;td style="width: 33.3333%; height: 27px;"&gt;Product&lt;/td&gt;
&lt;/tr&gt;
&lt;tr style="height: 27px;"&gt;
&lt;td style="width: 33.3333%; height: 27px;"&gt;H&lt;sup&gt;+&lt;/sup&gt;
&lt;/td&gt;
&lt;td style="width: 33.3333%; height: 27px;"&gt;1&lt;/td&gt;
&lt;td style="width: 33.3333%; height: 27px;"&gt;1&lt;/td&gt;
&lt;/tr&gt;
&lt;tr style="height: 27px;"&gt;
&lt;td style="width: 33.3333%; height: 27px;"&gt;C&lt;sub&gt;2&lt;/sub&gt;H&lt;sub&gt;3&lt;/sub&gt;O&lt;sub&gt;2&lt;/sub&gt;&lt;sup&gt;-&lt;/sup&gt;
&lt;/td&gt;
&lt;td style="width: 33.3333%; height: 27px;"&gt;1&lt;/td&gt;
&lt;td style="width: 33.3333%; height: 27px;"&gt;2&lt;/td&gt;
&lt;/tr&gt;
&lt;tr style="height: 27px;"&gt;
&lt;td style="width: 33.3333%; height: 27px;"&gt;Ca&lt;sup&gt;2+&lt;/sup&gt;
&lt;/td&gt;
&lt;td style="width: 33.3333%; height: 27px;"&gt;1&lt;/td&gt;
&lt;td style="width: 33.3333%; height: 27px;"&gt;1&lt;/td&gt;
&lt;/tr&gt;
&lt;tr style="height: 27px;"&gt;
&lt;td style="width: 33.3333%; height: 27px;"&gt;OH&lt;sup&gt;-&lt;/sup&gt;
&lt;/td&gt;
&lt;td style="width: 33.3333%; height: 27px;"&gt;2&lt;/td&gt;
&lt;td style="width: 33.3333%; height: 27px;"&gt;1&lt;/td&gt;
&lt;/tr&gt;
&lt;/tbody&gt;
&lt;/table&gt;
&lt;p&gt;Balance the acetate and hydroxide with coefficients.&lt;/p&gt;</mattext>
              </material>
            </flow_mat>
          </itemfeedback>
        </item>
EOD;
        $xml = $qtiImport->cleanUpXml($xml);
        $xml_array = json_decode(json_encode($xml), true);
        $title = $xml_array['@attributes']['title'] ?? 'None provided.';
        $htmlDom = new DOMDocument();
        foreach ($xml_array['itemmetadata']['qtimetadata']['qtimetadatafield'] as $value) {
            if ($value['fieldlabel'] === 'question_type') {
                $question_type = $value['fieldentry'];
            }
        }
        foreach ($xml_array['itemmetadata']['qtimetadata']['qtimetadatafield'] as $value) {
            if ($value['fieldlabel'] === 'question_type') {
                $question_type = $value['fieldentry'];
            }
        }
        dd($question_type);
        //For some questions it's saying multiple blanks, but I'm not convinced...
        dd($question_type);
        $xml_array = $qtiImport->processFillInMultipleBlanksQuestion($xml_array);
        dd($xml_array);


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
                $question_type = '';
                $non_technology_html = '';
                switch ($qti_job->qti_source) {
                    case('canvas'):
                        $canvas_import_info = $qtiImport->processCanvasImport($xml_array, $qti_import, $qti_job, request()->user()->id, $htmlDom, $question);
                        $xml_array = $canvas_import_info['xml_array'];
                        if ($canvas_import_info['non_technology_html']) {
                            $non_technology_html = $canvas_import_info['non_technology_html'];
                        }

                        if ($canvas_import_info['message']) {
                            $response['message'] = $canvas_import_info['message'];
                        }
                        $question_type = $canvas_import_info['question_type'];
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

                $qtiImport->updateByQuestionType($question, $question_type, $non_technology_html, $xml_array);

                $question->library = 'adapt';
                $question->title = $title;
                $question->page_id = 0;
                $question->technology_iframe = '';
                $question->author = $qti_job->author;
                $question->public = $qti_job->public;
                $question->folder_id = $qti_job->folder_id;
                $question->question_editor_user_id = $request->user()->id;
                $question->license = $qti_job->license;
                $question->license_version = $qti_job->license_version;
                $question->source_url = $qti_job->source_url;
                $question->qti_json_type = $question_type;
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
        } catch
        (Exception $e) {
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
