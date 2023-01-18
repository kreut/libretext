<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class QtiImport extends Model
{


    public function getMatchingJson(): string
    {
        return '{"prompt": "","termsToMatch": {}, "possibleMatches": {}}';
    }

    public function getSimpleChoiceJson(): string
    {
        return '{"prompt": "","simpleChoice": {}}';
    }

    public function getMultipleAnswersJson(): string
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

    public function processMatching($xml, $xml_array)
    {

        $matchings = json_decode($this->getMatchingJson(), true);
        $pattern = '/<varequal ([^>]*)>(.*?)<\/varequal>/i';
        preg_match_all($pattern, $xml, $matches);
        $terms_to_match = $matches[2];
        $matching_terms = $matches[1]; //"respident="response_936""
        foreach ($matching_terms as $key => $matching_term) {
            $matching_terms[$key] = preg_replace('~\D~', '', $matching_term);
        }
        $matching_terms_by_identifier = [];

        foreach ($terms_to_match as $key => $term_to_match) {
            $matching_terms_by_identifier[$matching_terms[$key]] = $term_to_match;
        }

        $feedback_by_identifier = [];
        if (isset($xml_array['itemfeedback'])) {
            foreach ($xml_array['itemfeedback'] as $item_feedback) {
                $identifier = str_replace('_fb', '', $item_feedback['@attributes']['ident']);
                $feedback = $item_feedback['flow_mat']['material']['mattext'];
                $feedback_by_identifier[$identifier] = $feedback;
            }
        }


        $matchings['prompt'] = $xml_array['presentation']['material']['mattext'];
        $identifiers = [];

        $possible_matches = [];
        foreach ($xml_array['presentation']['response_lid'] as $matching_info) {
            $identifier = str_replace('response_', '', $matching_info['@attributes']['ident']);
            $matchings['termsToMatch'][] = ['identifier' => $identifier,
                'termToMatch' => $matching_info['material']['mattext'],
                'matchingTermIdentifier' => $matching_terms_by_identifier[$identifier],
                'feedback' => $feedback_by_identifier[$identifier] ?? ''];
            foreach ($matching_info['render_choice'] as $render_choices) {
                foreach ($render_choices as $render_choice) {
                    $identifier = $render_choice['@attributes']['ident'];
                    if (!in_array($identifier, $identifiers)) {
                        $possible_matches[] = ['identifier' => $identifier,
                            'matchingTerm' => $render_choice['material']['mattext']];
                        $identifiers[] = $identifier;
                    }
                }
            }
        }
        $matchings['possibleMatches'] = $possible_matches;
        return $matchings;

    }

    /**
     * @param $xml_array
     * @return array
     */
    public function processNumerical($xml_array): array
    {
        $numerical_answer_array['prompt'] = $xml_array['presentation']['material']['mattext'];
        foreach ($xml_array['resprocessing']['respcondition'] as $key => $respcondition) {
            if (isset($respcondition['setvar'])) {
                $numerical_answer_array['correctResponse'] = [];
                $value = $respcondition['conditionvar']['or']['varequal'];
                $numerical_answer_array['correctResponse']['value'] = $value;
                $numerical_answer_array['correctResponse']['marginOfError'] = $value - $respcondition['conditionvar']['or']['and']['vargte'];
                break;
            }
        }
        if (isset($xml_array['itemfeedback'])) {
            foreach ($xml_array['itemfeedback'] as $feedback) {
                $identifier = $feedback['@attributes']['ident'];
                $feedback = $feedback['flow_mat']['material']['mattext'];
                switch ($identifier) {
                    case('general_fb'):
                        $numerical_answer_array['feedback']['any'] = $feedback;
                        break;
                    case('general_incorrect_fb'):
                        $numerical_answer_array['feedback']['incorrect'] = $feedback;
                        break;
                    case('correct_fb'):
                        $numerical_answer_array['feedback']['correct'] = $feedback;
                        break;
                }
            }
        }
        return $numerical_answer_array;
    }

    public function processMultipleAnswersQuestion($xml_array)
    {

        $multiple_answer_question_array = json_decode($this->getMultipleAnswersJson(), true);

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
        if (isset($item_feedback)) {
            foreach ($xml_array['itemfeedback'] as $item_feedback) {
                $identifier = str_replace('_fb', '', $item_feedback['@attributes']['ident']);
                $feedbacks[$identifier] = $item_feedback['flow_mat']['material']['mattext'];
            }
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
        $var_equal = $this->getSimpleChoiceCorrectResponse($xml_array);

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
        if ($question_type === 'multiple_choice') {
            $simple_choice_array = $this->getFeedBack($xml_array, $simple_choice_array);
        }
        return $simple_choice_array;
    }

    /**
     * @param array $xml_array
     * @param array $simple_choice_array
     * @return array
     */
    public function getFeedback(array $xml_array, array $simple_choice_array): array
    {
        if (isset($xml_array['itemfeedback'])) {
            foreach ($xml_array['itemfeedback'] as $feedback) {
                if (isset($feedback['@attributes']['ident'])) {
                    if ($feedback['@attributes']['ident'] === 'general_incorrect_fb') {
                        $simple_choice_array['feedback']['incorrect'] = $feedback['flow_mat']['material']['mattext'];
                    } else if ($feedback['@attributes']['ident'] === 'general_fb') {
                        $simple_choice_array['feedback']['any'] = $feedback['flow_mat']['material']['mattext'];
                    } else {
                        $identifier = str_replace('_fb', '', $feedback['@attributes']['ident']);
                        $simple_choice_array['feedback'][$identifier] = $feedback['flow_mat']['material']['mattext'];
                    }
                }
            }
        }
        return $simple_choice_array;
    }

    /**
     * @throws Exception
     */
    public
    function getSimpleChoiceCorrectResponse($xml_array)
    {
        if (isset($xml_array['resprocessing']['respcondition']['setvar'])
            && (int)$xml_array['resprocessing']['respcondition']['setvar'] === 100) {
            return $xml_array['resprocessing']['respcondition']['conditionvar']['varequal'];
        }
        foreach ($xml_array['resprocessing']['respcondition'] as $key => $response_condition) {
            if (isset($response_condition['setvar']) && (int)$response_condition['setvar'] === 100) {
                return $response_condition['conditionvar']['varequal'];
            }
        }
        throw new Exception("No correct response for this question.");

    }

    public
    function getVarEqual($xml_array)
    {
        return $xml_array['resprocessing']['respcondition']['conditionvar']['varequal'];

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
