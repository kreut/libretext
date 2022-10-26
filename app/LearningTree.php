<?php

namespace App;

use App\Exceptions\TreeNotCreatedInAdaptException;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LearningTree extends Model
{
    protected $guarded = [];

    /**
     * @return HasMany
     */
    public function learningTreeHistories(): HasMany
    {
        return $this->hasMany('App\LearningTreeHistory');
    }

    public function getBranchStructure()
    {
        $blocks = json_decode($this->learning_tree)->blocks;

        $branches = [];
        foreach ($blocks as $block) {
            if ($block->parent === 0) {
                $branches[] = $block->id;
            }
        }
        $twigs_by_branch_id = [];
        foreach ($branches as $branch) {
            $twigs_by_branch_id[$branch] = [$branch];
            foreach ($blocks as $block) {
                if (in_array($block->parent, $twigs_by_branch_id[$branch])) {
                    $twigs_by_branch_id[$branch][] = $block->id;
                }
            }
        }
        return $twigs_by_branch_id;

    }

    /**
     * @param array $learning_tree_branch_structure
     * @param int $instructor_id
     * @return array
     * @throws TreeNotCreatedInAdaptException
     */
    public function getBranchAndTwigInfo(array $learning_tree_branch_structure, int $instructor_id): array
    {
        $branches_with_question_info = [];
        $blocks = json_decode($this->learning_tree)->blocks;
        foreach ($learning_tree_branch_structure as $branch => $twigs) {
            foreach ($twigs as $twig) {
                foreach ($blocks as $block) {
                    if ($block->id === $twig) {
                        $branches_with_question_info[$branch][$twig] = [
                            'id' => $twig,
                            'question_id' => $this->getBlockInfoByKey($block, 'question_id')
                        ];
                    }
                }
            }
        }
        $questions = DB::table('questions');
        foreach ($branches_with_question_info as $twigs) {
            foreach ($twigs as $twig) {
                $questions = $questions->orWhere(function ($query) use ($twig) {
                    $query->where('id', $twig['question_id']);
                });
            }
        }

        $questions = $questions->select('questions.id', 'title',  'technology')->get();

        $question_ids = [];
        foreach ($branches_with_question_info as $key => $twigs) {
            foreach ($twigs as $twig) {
                foreach ($questions as $question) {
                    $question_ids[] = $question->id;
                    if ((int)$question->id === (int)$twig['question_id']) {
                        $branches_with_question_info[$key][$twig['id']]['question_info'] = $question;
                    }
                }
            }
        }


        $branch_descriptions = DB::table('branches')
            ->whereIn('question_id', $question_ids)
            ->where('learning_tree_id', $this->id)
            ->where('user_id', $instructor_id)
            ->select('question_id', 'description')
            ->get();

        $branch_descriptions_by_question_id = [];
        foreach ($branch_descriptions as $branch_description) {
            $branch_descriptions_by_question_id[$branch_description->question_id] = $branch_description->description;
        }

        $branch_and_twig_info = [];
       foreach ($branches_with_question_info as $branch_id => $twigs) {

            $branch_and_twig_info[$branch_id] = [];
            $branch_and_twig_info[$branch_id]['twigs'] = $twigs;
            //get the description or use the first twig which is the branch
            if (!isset($twigs[key($twigs)]['question_info'])){
                throw new TreeNotCreatedInAdaptException("Learning Tree $this->id has remediation nodes that were not created in ADAPT.  Please move them to ADAPT and try again.");
            }

            ///get number of assessments and non-assessments
            $num_assessments = 0;
            foreach ($twigs as $twig_id => $twig) {
                if ($twig['question_info']->technology !== 'text') {
                    $num_assessments++;
                }
                $question_id =   $branch_and_twig_info[$branch_id]['twigs'][$twig_id]['question_info']->id;
                $title = $branch_and_twig_info[$branch_id]['twigs'][$twig_id]['question_info']->title;
                $branch_and_twig_info[$branch_id]['twigs'][$twig_id]['question_info']->description=$branch_descriptions_by_question_id[$question_id] ?? $title;

            }

            $branch_and_twig_info[$branch_id]['id'] = $branch_id;
            $branch_and_twig_info[$branch_id]['assessments'] = $num_assessments;
            $branch_and_twig_info[$branch_id]['expositions'] = count($twigs) - $num_assessments;
        }

        return array_values($branch_and_twig_info);
    }

    /**
     * @param $block
     * @param $key
     * @return string
     */
    public
    function getBlockInfoByKey($block, $key): string
    {
        foreach ($block->data as $data) {
            if ($data->name === $key) {
                return $data->value;
            }
        }
        return "Key does not exist";
    }


}
