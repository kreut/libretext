<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function getBranchesWithQuestionInfo(array $learning_tree_branch_structure)
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

        $questions = $questions->select('id', 'library', 'title','page_id', 'technology')->get();
        foreach ($branches_with_question_info as $key => $twigs) {
            foreach ($twigs as $twig) {
                foreach ($questions as $question) {
                    if ($question->library === $twig['library'] && (int)$question->page_id === (int)$twig['page_id']) {
                        $branches_with_question_info[$key][$twig['id']]['question_info'] = $question;
                    }
                }
            }
        }

        return $branches_with_question_info;
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
