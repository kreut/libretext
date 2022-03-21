<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function getBranchAndTwigInfo(array $learning_tree_branch_structure)
    {
        $branches_with_question_info = [];
        $blocks = json_decode($this->learning_tree)->blocks;
        foreach ($learning_tree_branch_structure as $branch => $twigs) {
            foreach ($twigs as $twig) {
                foreach ($blocks as $block) {
                    if ($block->id === $twig) {
                        $branches_with_question_info[$branch][$twig] = [
                            'id' => $twig,
                            'library' => $this->getBlockInfoByKey($block, 'library'),
                            'page_id' => $this->getBlockInfoByKey($block, 'page_id')
                        ];
                    }
                }
            }
        }
        $questions = DB::table('questions');
        foreach ($branches_with_question_info as $twigs) {
            foreach ($twigs as $twig) {
                $questions = $questions->orWhere(function ($query) use ($twig) {
                    $query->where('library', $twig['library']);
                    $query->where('page_id', $twig['page_id']);
                });
            }
        }

        $questions = $questions->select('questions.id', 'library', 'title', 'page_id', 'technology')->get();
        $question_ids = [];
        foreach ($branches_with_question_info as $key => $twigs) {
            foreach ($twigs as $twig) {
                foreach ($questions as $question) {
                    $question_ids[] = $question->id;
                    if ($question->library === $twig['library'] && (int)$question->page_id === (int)$twig['page_id']) {
                        $branches_with_question_info[$key][$twig['id']]['question_info'] = $question;
                    }
                }
            }
        }

        $branch_descriptions = DB::table('branches')
            ->whereIn('question_id', $question_ids)
            ->where('learning_tree_id', $this->id)
            ->where('user_id', Auth::user()->id)
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
            $branch_and_twig_info[$branch_id]['description'] = $branch_descriptions_by_question_id[$twigs[$branch_id]['question_info']->id]
                ?? $twigs[key($twigs)]['question_info']->title;

            ///get number of assessments and non-assessments
            $num_assessments = 0;
            foreach ($twigs as $twig) {
                if ($twig['question_info']->technology !== 'text') {
                    $num_assessments++;
                }
            }
            $branch_and_twig_info[$branch_id]['assessments'] = $num_assessments;
            $branch_and_twig_info[$branch_id]['expositions'] = count($twigs) - $num_assessments;

        }

        return $branch_and_twig_info;
    }

    /**
     * @param $block
     * @param $key
     * @return string
     */
    public
    function getBlockInfoByKey($block, $key)
    {
        foreach ($block->data as $data) {
            if ($data->name === $key) {
                return $data->value;
            }
        }
        return "Key does not exist";
    }


}
