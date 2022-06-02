<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class QtiImport extends Model
{

    public function getSimpleChoiceJson(): string
    {
        return '{"prompt": "","simpleChoice": {}}';
    }

    public function getMultipleAnswersQuestionJson(): string
    {
        return '{ "prompt": "","simpleChoice": {}}';
    }

    /**
     * @return string
     */
    public function getFillInTheBlankJson(): string
    {
        return '{
        "@attributes": {},
  "responseDeclaration": { "correctResponse": {}},
  "itemBody": {"textEntryInteraction" : ""}
}';
    }


    public function getMultipleDropdownsJson(): string
    {
        return '{
        "@attributes": {},
    "correctResponse": {}
  },
  "itemBody": {"dropdown" :""},
  "responseDeclaration": []
}';
    }

    public function processMultipleAnswersQuestion($xml_array)
    {

        $multiple_answer_question_array = json_decode($this->getMultipleAnswersQuestionJson(), true);

        $multiple_answer_question_array['prompt'] = $xml_array['presentation']['material']['mattext'];
        $feedbacks = [];
        $correct_responses = [];
        foreach ($xml_array['resprocessing']['respcondition'] as $key => $respcondition) {
            if (isset($respcondition['setvar'])) {
                $correct_responses = is_array($respcondition['conditionvar']['and']['varequal'])
                    ? $respcondition['conditionvar']['and']['varequal']
                    : [$respcondition['conditionvar']['and']['varequal']];
            }
        }
        foreach ($xml_array['itemfeedback'] as $item_feedback) {
            $identifier = str_replace('_fb', '', $item_feedback['@attributes']['ident']);
            $feedbacks[$identifier] = $item_feedback['flow_mat']['material']['mattext'];
        }

        $render_choices = $xml_array['presentation']['response_lid']['render_choice']['response_label'];
        foreach ($render_choices as $render_choice) {
            $identifier = $render_choice['@attributes']['ident'];
            $multiple_answer_question_array['simpleChoice'][] = [
                'identifier' => $render_choice['@attributes']['ident'],
                'value' => $render_choice['material']['mattext'],
                'correctResponse' => in_array($identifier, $correct_responses),
                'feedback' => $feedbacks[$identifier] ?? ''];
        }

        return $multiple_answer_question_array;

    }

    public
    function processMultipleDropDowns($xml_array)
    {
        $multiple_drop_downs_array = json_decode($this->getMultipleDropDownsJson(), true);
        $inline_choice_interactions = [];
        $multiple_drop_downs_array['inline_choice_interactions'] = [];
        foreach ($xml_array['presentation']['response_lid'] as $value) {
            $identifier = $value['material']['mattext'];
            $inline_choice_interactions[$identifier] = [];
            foreach ($value['render_choice']['response_label'] as $choice) {
                if (!isset($choice['material']['mattext']['@attributes'])) {
                    $inline_choice_interactions[$identifier][] = [
                        'value' => $choice['@attributes']['ident'],
                        'text' => $choice['material']['mattext'],
                        'correctResponse' => !count($inline_choice_interactions[$identifier])
                    ];

                }
            }
            $multiple_drop_downs_array['inline_choice_interactions'][$identifier] = $inline_choice_interactions[$identifier];
        }

        $multiple_drop_downs_array['itemBody'] = $xml_array['presentation']['material']['mattext'];
        $multiple_drop_downs_array['responseDeclaration']['correctResponse'] = [];
        foreach ($xml_array['resprocessing']['respcondition'] as $response) {
            $multiple_drop_downs_array['responseDeclaration']['correctResponse'][] = ['value' => $response['conditionvar']['varequal']];
        }

        return $multiple_drop_downs_array;

    }

    public
    function processFillInMultipleBlanksQuestion($xml_array)
    {
        $fill_in_the_blank_array = json_decode($this->getFillInTheBlankJson(), true);
        $text_entry_interaction = $xml_array['presentation']['material']['mattext'];


        foreach ($xml_array['presentation']['response_lid'] as $value) {
            if (!isset($value['render_choice']['response_label']['material'])) {

                Log::info(print_r($xml_array, true));
            }
            $fill_in_the_blank_array['responseDeclaration']['correctResponse'][] = ['value' => $value['render_choice']['response_label']['material']['mattext']];
        }
        $pattern = '/\[(.*?)\]/';
        $fill_in_the_blank_array['itemBody']['textEntryInteraction'] = preg_replace_callback($pattern, function () {
            return "<u></u>";
        }, $text_entry_interaction);
        return $fill_in_the_blank_array;


    }


    public
    function processShortAnswerQuestion($xml_array)
    {
        $short_answer_question_array = json_decode($this->getFillInTheBlankJson(), true);
        $text_entry_interaction = $xml_array['presentation']['material']['mattext'];
        $var_equal = $this->getVarEqual($xml_array);
        $pattern = '/([_])\1{1,}/';
        $short_answer_question_array['itemBody']['textEntryInteraction'] = preg_replace_callback($pattern, function () {
            return "<u></u>";
        }, $text_entry_interaction);
        $short_answer_question_array['responseDeclaration']['correctResponse'] = [['value' => $var_equal]];
        return $short_answer_question_array;
    }

    /**
     * @throws Exception
     */
    public
    function processSimpleChoice($xml_array, $question_type)
    {
        $simple_choice_array = json_decode($this->getSimpleChoiceJson(), true);
        $simple_choice_array['prompt'] = $xml_array['presentation']['material']['mattext'];

        // why is there any array in the instructor's folder but not mine?  (maybe course vs single quiz?)
        $var_equal = $this->getVarEqual($xml_array);

        $simple_choice_array['simpleChoice'] = [];

        $response_labels = $xml_array['presentation']['response_lid']['render_choice']['response_label'];
        if ($question_type === 'multiple_choice') {
            shuffle($response_labels);
        }
        $xml_array['presentation']['response_lid']['render_choice']['response_label'] = $response_labels;
        foreach ($xml_array['presentation']['response_lid']['render_choice']['response_label'] as $response) {
            $simple_choice_array['simpleChoice'][] = ['identifier' => $response['@attributes']['ident'],
                'value' => $response['material']['mattext'],
                'correctResponse' => $response['@attributes']['ident'] === $var_equal];
        }

        return $simple_choice_array;
    }

    public
    function getVarEqual($xml_array)
    {
        return isset($xml_array['resprocessing']['respcondition'][0])
            ? $xml_array['resprocessing']['respcondition'][0]['conditionvar']['varequal']
            : $xml_array['resprocessing']['respcondition']['conditionvar']['varequal'];

    }

    /**
     * @throws Exception
     */
    public
    function cleanUpXml(string $xml)
    {
        //$pattern = '/<simpleChoice ([^>]*)>([^<>]*)<\/simpleChoice>/i';
        //$pattern = '/<simpleChoice ([^>]*)>\K.*?(?=<\/simpleChoice>)/';
        $pattern = '/<simpleChoice ([^>]*)>(.*?)<\/simpleChoice>/i';
        $xml = preg_replace_callback($pattern, function ($match) {
            return '<simpleChoice ' . $match[1] . '><value>' . htmlentities($match[2]) . '</value></simpleChoice>';
        }, $xml);
        // dd($xml);
        //$xml = preg_replace('/<([^ ]+) ([^>]*)>([^<>]*)<\/\\1>/i', '<$1 $2><value>$3</value></$1>', $xml);

        // dd($xml);


        //Bug: https://stackoverflow.com/questions/8563073/disappearing-attributes-in-php-simplexml-object
        $xml = str_replace('<prompt>', '<prompt><![CDATA[', $xml);
        $xml = str_replace('</prompt>', ']]></prompt>', $xml);
        $xml = str_replace("\n", "", $xml);
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml, null, LIBXML_NOCDATA);
        $errors = libxml_get_errors();
        if ($errors) {
            $message = trim($errors[0]->message);
            throw new Exception("XML is not well-formed:  $message");
        }
        return $xml;
    }
}
