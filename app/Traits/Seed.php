<?php


namespace App\Traits;

use App\Assignment;
use App\Question;
use Exception;

trait Seed

{
    /**
     * @throws Exception
     */
    public function createSeedByTechnologyAssignmentAndQuestion(Assignment $assignment, Question $question, $reset_node_after_incorrect_attempt = false)
    {
        switch ($question->technology) {
            case('webwork'):
                $seed = $assignment->algorithmic || $reset_node_after_incorrect_attempt ? rand(1, 99999) : config('myconfig.webwork_seed');
                break;
            case('imathas'):
                $seed = $assignment->algorithmic || $reset_node_after_incorrect_attempt ? rand(1, 99999) : config('myconfig.imathas_seed');
                break;
            case('qti'):
                $qti_array = json_decode($question->qti_json, true);
                $question_type = $qti_array['questionType'];
                $seed = '';
                if (in_array($question_type, [
                    'submit_molecule',
                    'true_false',
                    'fill_in_the_blank',
                    'numerical',
                    'matrix_multiple_choice',
                    'highlight_text',
                    'highlight_table',
                    'matrix_multiple_response'])) {
                    return $seed;
                }
                switch ($question_type) {
                    case('drag_and_drop_cloze'):
                        $seed = [];
                        foreach (['correctResponses', 'distractors'] as $group)
                            foreach ($qti_array[$group] as $response) {
                                $seed[] = $response['identifier'];
                            }
                        shuffle($seed);
                        $seed = json_encode($seed);
                        break;
                    case('drop_down_table'):
                        $seed = [];
                        foreach ($qti_array['rows'] as $row) {
                            $header = $row['header'];
                            $seed[$header] = [];
                            foreach ($row['responses'] as $value) {
                                $seed[$header][] = $value['identifier'];
                            }
                            shuffle($seed[$header]);
                        }
                        $seed = json_encode($seed);
                        break;
                    case('multiple_response_grouping'):
                        $seed = [];
                        foreach ($qti_array['rows'] as $row) {
                            $grouping = $row['grouping'];
                            $seed[$grouping] = [];
                            foreach ($row['responses'] as $value) {
                                $seed[$grouping][] = $value['identifier'];
                            }
                            shuffle($seed[$grouping]);
                        }
                        $seed = json_encode($seed);
                        break;
                    case('multiple_response_select_n'):
                    case('multiple_response_select_all_that_apply'):
                        $seed = [];
                        foreach ($qti_array['responses'] as $response) {
                            $seed[] = $response['identifier'];
                        }
                        shuffle($seed);
                        $seed = json_encode($seed);
                        break;
                    case('bow_tie'):
                        $seed = [];
                        foreach (['actionsToTake', 'potentialConditions', 'parametersToMonitor'] as $group) {
                            foreach ($qti_array[$group] as $value) {
                                $seed[$group][] = $value['identifier'];
                            }
                            shuffle($seed[$group]);
                        }
                        $seed = json_encode($seed);
                        break;
                    case('matching'):
                        $seed = [];
                        foreach ($qti_array['possibleMatches'] as $possible_match) {
                            $seed[] = $possible_match['identifier'];
                        }
                        shuffle($seed);
                        $seed = json_encode($seed);
                        break;
                    case('drop_down_rationale'):
                    case('select_choice'):
                    case('drop_down_rationale_triad'):
                        $seed = [];
                        foreach ($qti_array['inline_choice_interactions'] as $identifier => $choices) {
                            $indices = range(0, count($choices) - 1);
                            shuffle($indices);
                            $seed[$identifier] = $indices;
                        }
                        $seed = json_encode($seed);
                        break;
                    case('multiple_choice'):
                    case('multiple_answers'):
                        $seed = [];
                        $choices = $qti_array['simpleChoice'];
                        $randomize_order = true;
                        if ($question_type === 'multiple_choice') {
                            $randomize_order = !isset($qti_array['randomizeOrder']) || $qti_array['randomizeOrder'] === 'yes';
                        }
                        if ($randomize_order) {
                            shuffle($choices);
                        }
                        foreach ($choices as $choice) {
                            $seed[] = $choice['identifier'];
                        }
                        $seed = json_encode($seed);
                        break;
                    default:
                        throw new Exception("QTI $question_type does not generate a seed.");
                }
                break;
            default:
                throw new Exception("$question->technology should not be generating a seed.");
        }
        return $seed;
    }
}
