<?php

namespace App\Http\Controllers;


use App\Branch;
use App\EmptyLearningTreeNode;
use App\Helpers\Helper;
use App\Http\Requests\ImportLearningTreesRequest;
use App\Http\Requests\StoreLearningTreeInfo;
use App\Http\Requests\UpdateLearningTreeInfo;
use App\Http\Requests\UpdateNode;
use App\LearningTree;
use App\LearningTreeHistory;
use App\Libretext;
use App\Question;
use App\SavedQuestionsFolder;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Exceptions\Handler;
use \Exception;
use Illuminate\Support\Facades\Storage;

class LearningTreeController extends Controller
{


    public
    function getAll(Request      $request,
                    LearningTree $learningTree): array
    {
        $per_page = $request->per_page;
        $current_page = $request->current_page;
        $author = $request->author;
        $title = $request->title;

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAll', $learningTree);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $learning_tree_ids = DB::table('learning_trees')
                ->select('id')
                ->where(function ($query) use ($request) {
                    $query->where('public', 1)
                        ->orWhere('user_id', '=', $request->user()->id);
                });

            if ($title) {
                $learning_tree_ids = $learning_tree_ids->where('title', 'LIKE', "%$title%");
            }
            if ($author) {
                $all_author_ids = DB::table('learning_trees')
                    ->get('user_id')
                    ->pluck('user_id');
                $users = DB::table('users')
                    ->select('')
                    ->whereIn('id', $all_author_ids)
                    ->select('id', DB::raw('CONCAT(first_name, " ", last_name) AS name'))
                    ->get();
                $author_ids = [];
                foreach ($users as $user) {
                    if (stripos($user->name, $author) !== false) {
                        $author_ids[] = $user->id;
                    }

                }
                $learning_tree_ids = $learning_tree_ids->whereIn('user_id', $author_ids);
            }


            $total_rows = $learning_tree_ids->count();

            $learning_tree_ids = $learning_tree_ids
                ->orderBy('id')
                ->skip($per_page * ($current_page - 1))
                ->take($per_page)
                ->get()
                ->sortBy('id')
                ->pluck('id')
                ->toArray();

            $learning_trees = DB::table('learning_trees')
                ->join('users', 'learning_trees.user_id', '=', 'users.id')
                ->select('learning_trees.id',
                    'title',
                    DB::raw('CONCAT(first_name, " ", last_name) AS author')
                )
                ->whereIn('learning_trees.id', $learning_tree_ids)
                ->get();

            $response['learning_trees'] = $learning_trees;
            $response['total_rows'] = $total_rows;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve all of the learning trees.  Please try again or contact us for assistance.";
        }

        return $response;

    }


    public function getLearningTreeByAssignmentQuestion()
    {
///after submitting, check if there's a learning tree
/// if there is


    }

    /**
     * @param ImportLearningTreesRequest $request
     * @param LearningTree $learningTree
     * @return array
     * @throws Exception
     */
    public function import(ImportLearningTreesRequest $request,
                           LearningTree               $learningTree): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('import', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $request->validated();


            $learning_tree_ids = explode(',', $request->learning_tree_ids);
            DB::beginTransaction();
            foreach ($learning_tree_ids as $learning_tree_id) {
                $learning_tree_to_import = LearningTree::find(trim($learning_tree_id))
                    ->replicate()
                    ->fill(['user_id' => $request->user()->id]);
                $learning_tree_to_import->save();

                $learningTreeHistory = new LearningTreeHistory();
                $learningTreeHistory->learning_tree = $learning_tree_to_import->learning_tree;
                $learningTreeHistory->learning_tree_id = $learning_tree_to_import->id;
                $learningTreeHistory->root_node_library = $learning_tree_to_import->root_node_library;
                $learningTreeHistory->root_node_page_id = $learning_tree_to_import->root_node_page_id;
                $learningTreeHistory->save();

            }
            $plural = str_contains($request->learning_tree_ids, ',') ? "s have been" : ' was';
            $response['type'] = 'success';
            $response['message'] = "The Learning Tree$plural imported.";

            DB::commit();
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error importing the learning trees.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param UpdateNode $request
     * @param LearningTree $learningTree
     * @return array
     * @throws Exception
     */
    public function updateNode(UpdateNode   $request,
                               LearningTree $learningTree): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('updateNode', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $response['type'] = 'error';
        if ($request->original_library !== $request->library || (int)($request->original_page_id) !== (int)$request->page_id) {
            $message = $this->learningTreeInAssignment($request, $learningTree, 'update the node');
            if ($message) {
                $response['message'] = $message;
                return $response;
            }
        }
        try {
            $data = $request->validated();

            $validated_node = $this->validateLearningTreeNode($data['library'], $data['page_id']);
            $question = DB::table('questions')
                ->where('library', $data['library'])
                ->where('page_id', $data['page_id'])
                ->first();
            if (!$question) {
                $response['message'] = "No question exists with a library of {$data['library']} and a page id of {$data['page_id']}.";
                return $response;
            }
            if ($validated_node['type'] === 'error') {
                $response['message'] = $validated_node['message'];
                return $response;
            }
            if ($validated_node['body'] === '') {
                $response['message'] = "Are you sure that's a valid page id?  We're not finding any content on that page.";
                return $response;
            }

            DB::beginTransaction();
            if (!$request->is_root_node) {
                $branch = DB::table('branches')
                    ->where('user_id', $request->user()->id)
                    ->where('learning_tree_id', $learningTree->id)
                    ->where('question_id', $question->id)
                    ->first();
                if (!$branch) {
                    $branch = new Branch();
                    $branch->user_id = $request->user()->id;
                    $branch->learning_tree_id = $learningTree->id;
                    $branch->question_id = $question->id;
                } else {
                    $branch = Branch::find($branch->id);
                }
                $branch->description = $data['branch_description'];
                $branch->save();
                $learning_tree_node_learning_outcome = DB::table('learning_tree_node_learning_outcome')
                    ->where('user_id', $request->user()->id)
                    ->where('learning_tree_id', $learningTree->id)
                    ->where('question_id', $question->id)
                    ->first();
                if ($request->learning_outcome) {
                    $learning_outcome = DB::table('learning_outcomes')
                        ->where('id', $request->learning_outcome)
                        ->first();
                    if (!$learning_outcome) {
                        throw new Exception ("$request->learning_outcome is not a valid learning outcome ID.");
                    }

                    if (!$learning_tree_node_learning_outcome) {
                        DB::table('learning_tree_node_learning_outcome')->insert([
                            'user_id' => $request->user()->id,
                            'learning_tree_id' => $learningTree->id,
                            'question_id' => $question->id,
                            'learning_outcome_id' => $learning_outcome->id,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    } else {
                        DB::table('learning_tree_node_learning_outcome')
                            ->where('id', $learning_tree_node_learning_outcome->id)
                            ->update(['learning_outcome_id' => $learning_outcome->id, 'updated_at' => now()]);
                    }
                } else {
                    if ($learning_tree_node_learning_outcome) {
                        DB::table('learning_tree_node_learning_outcome')
                            ->where('id', $learning_tree_node_learning_outcome->id)
                            ->delete();
                    }

                }
            }

            $response['title'] = $validated_node['title'];
            $response['type'] = 'success';
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the node: {$e->getMessage()}";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param LearningTree $learningTree
     * @return array
     * @throws Exception
     */
    public function createLearningTreeFromTemplate(Request $request, LearningTree $learningTree)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('createLearningTreeFromTemplate', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $response['type'] = 'error';

        try {
            DB::beginTransaction();
            $new_learning_tree = $learningTree->replicate();
            $new_learning_tree->title = $new_learning_tree->title . ' copy';
            $new_learning_tree->save();


            $learningTreeHistory = new LearningTreeHistory();
            $learningTreeHistory->root_node_library = $new_learning_tree->root_node_library;
            $learningTreeHistory->root_node_page_id = $new_learning_tree->root_node_page_id;
            $learningTreeHistory->learning_tree = $new_learning_tree->learning_tree;
            $learningTreeHistory->learning_tree_id = $new_learning_tree->id;
            $learningTreeHistory->save();
            DB::commit();
            $response['message'] = "The Learning Tree has been created.";
            $response['type'] = 'success';

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating a learning tree from this template.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function learningTreeExists(Request $request)
    {
        $response['type'] = 'error';

        try {
            $learning_tree = DB::table('learning_trees')->where('id', $request->learning_tree_id)->first();
            if (!$learning_tree) {
                $response['message'] = "We were not able to locate that learning tree.";
                return $response;
            }
            if (!$learning_tree->learning_tree) {
                $response['message'] = "You cannot add an empty learning tree to an assignment.";
                return $response;
            }
            if (count(json_decode($learning_tree->learning_tree)->blocks) === 1) {
                $response['message'] = "Your learning tree only has a single node. Please add at least one branch before adding this to an assignment.";
                return $response;
            }
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error determining whether the learning tree exists.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function index(Request $request, LearningTree $learningTree)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('index', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $response['type'] = 'error';

        try {
            $response['learning_trees'] = $learningTree->where('user_id', Auth::user()->id)->get();
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your learning trees.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param LearningTree $learningTree
     * @param LearningTreeHistory $learningTreeHistory
     * @return array
     * @throws Exception
     */
    public function updateLearningTree(Request             $request,
                                       LearningTree        $learningTree,
                                       LearningTreeHistory $learningTreeHistory): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $response['type'] = 'error';
        $learning_tree_old = json_decode($learningTree->learning_tree, true);
        $learning_tree_parsed = str_replace('\"', "'", $request->learning_tree);
        $learning_tree_new = json_decode($learning_tree_parsed, true);
        $no_change = $learning_tree_old === $learning_tree_new;

        if ($no_change) {
            $response['type'] = 'no_change';

        } else {
            try {
                $learningTree->learning_tree = $learning_tree_parsed;
                $blocks = json_decode($learningTree->learning_tree, true)['blocks'][0]['data'];
                $root_node_library = $root_node_page_id = null;
                foreach ($blocks as $block) {
                    if ($block['name'] === 'library') {
                        $root_node_library = $block['value'];
                    }
                    if ($block['name'] === 'page_id') {
                        $root_node_page_id = $block['value'];
                    }
                }
                if (!$root_node_library || !$root_node_page_id) {
                    $response['message'] = 'We could not get data from the root node.  Please try again or contact us for assistance.';
                    return $response;
                }
                $learningTree->root_node_library = $root_node_library;
                $learningTree->root_node_page_id = $root_node_page_id;

                DB::beginTransaction();
                $learningTree->save();
                $this->saveLearningTreeToHistory($learningTree->root_node_library,
                    $learningTree->root_node_page_id,
                    $learningTree,
                    $learningTreeHistory);
                $response['type'] = 'success';
                $response['message'] = "The learning tree has been saved.";
                $response['no_change'] = $no_change;
                $response['can_undo'] = $learningTreeHistory->where('learning_tree_id', $learningTree->id)->get()->count() > 1;
                DB::commit();
            } catch (Exception $e) {
                DB::rollback();
                $h = new Handler(app());
                $h->report($e);
                $response['message'] = "There was an error saving the learning tree.  Please try again or contact us for assistance.";
            }
        }
        return $response;

    }

    public function saveLearningTreeToHistory(string $library, int $page_id, LearningTree $learningTree, LearningTreeHistory $learningTreeHistory)
    {
        $learningTreeHistory->root_node_library = $library;
        $learningTreeHistory->root_node_page_id = $page_id;
        $learningTreeHistory->learning_tree_id = $learningTree->id;
        $learningTreeHistory->learning_tree = $learningTree->learning_tree;
        $learningTreeHistory->save();
    }

    /**
     * @param UpdateLearningTreeInfo $request
     * @param LearningTree $learningTree
     * @return array
     * @throws Exception
     */
    public function updateLearningTreeInfo(UpdateLearningTreeInfo $request, LearningTree $learningTree): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $response['type'] = 'error';


        try {

            $data = $request->validated();
            $learningTree->title = $data['title'];
            $learningTree->description = $data['description'];
            $learningTree->public = $data['public'];
            $learningTree->save();

            $response['type'] = 'success';
            $response['message'] = "The Learning Tree has been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the learning tree.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param StoreLearningTreeInfo $request
     * @param LearningTree $learningTree
     * @return array
     * @throws Exception
     */
    public function storeLearningTreeInfo(StoreLearningTreeInfo $request,
                                          LearningTree          $learningTree): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $response['type'] = 'error';

        try {

            $data = $request->validated();
            $learningTree->title = $data['title'];
            $learningTree->description = $data['description'];
            $learningTree->public = $data['public'];
            $learningTree->user_id = Auth::user()->id;
            $learningTree->root_node_page_id = 1;
            $learningTree->root_node_library = $learningTree->learning_tree = '';
            DB::beginTransaction();
            $learningTree->save();

            $response['type'] = 'success';
            $response['message'] = "The Learning Tree has been created.";
            $response['learning_tree_id'] = $learningTree->id;
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving the learning tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param $title
     * @return string
     */
    public function shortenTitle($title): string
    {
        $title = trim($title);
        return strlen($title) < 28 ? $title : substr($title, 0, 23) . '...';
    }

    public function getRootNode(string $title, string $library_value, string $library_text, string $library_color, int $page_id)
    {
        $html = "<div class='blockelem noselect block' style='left: 363px; top: 215px; border: 2px solid; color: $library_color;'><input type='hidden' name='blockelemtype' class='blockelemtype' value='1'><input type='hidden' name='blockid' class='blockid' value='0'><div class='blockyleft'><input type='hidden' name='page_id' value='$page_id'><input type='hidden' name='library' value='$library_value'><p class='blockyname'><img src='/assets/img/{$library_value}.svg'><span class='library'>{$library_text}</span> - <span class='page_id'>$page_id</span></p></div><div class='blockydiv'></div><div class='blockyinfo'>$title</div></div><div class='indicator invisible' style='left: 154px; top: 119px;'></div>";
        return <<<EOT
 {"html":"$html","blockarr":[{"childwidth":318,"parent":-1,"id":0,"x":825,"y":274,"width":318,"height":109}],"blocks":[{"id":0,"parent":-1,"data":[{"name":"blockelemtype","value":"1"},{"name":"blockid","value":"0"}],"attr":[{"class":"blockelem noselect block"},{"style":"left: 363px; top: 215px; border: 2px solid; color: {$library_color};"}]}]}
EOT;


    }

    public function getLearningTreeByUserAndQuestionId($user_id, $question_id)
    {
        return DB::table('learning_trees')
            ->where('question_id', $question_id)
            ->where('user_id', $user_id)
            ->pluck('learning_tree');
    }

    public function getDefaultLearningTreeByQuestionId(int $question_id)
    {
        return DB::table('learning_trees')
            ->where('question_id', $question_id)
            ->orderBy('created_at', 'asc')
            ->pluck('learning_tree');
    }

    public function getNodeLibraryTextFromLearningTree($learning_tree)
    {
        $re = '/(?<=<span class=\'library\'>).*?(?=<\/span>)/m';
        preg_match($re, $learning_tree, $matches);

        return $matches[0] ?? 'Could not find library';
    }

    public function getNodePageIdFromLearningTree($learning_tree)
    {
        $re = '/(?<=<span class=\'page_id\'>).*?(?=<\/span>)/m';
        preg_match($re, $learning_tree, $matches);

        return $matches[0] ?? 'Could not find Page Id';
    }

    public function show(Request             $request,
                         LearningTree        $learningTree,
                         LearningTreeHistory $learningTreeHistory)
    {
        //anybody who is logged in can do this!
        $response['type'] = 'error';
        try {

            $response['type'] = 'success';
            $response['learning_tree'] = $learningTree->learning_tree;
            $response['title'] = $learningTree->title;
            $response['description'] = $learningTree->description;
            $response['public'] = $learningTree->public;
            $response['library'] = $this->getNodeLibraryTextFromLearningTree($learningTree->learning_tree);
            $response['page_id'] = $this->getNodePageIdFromLearningTree($learningTree->learning_tree);
            $response['author_id'] = $learningTree->user_id;
            $response['can_undo'] = $learningTreeHistory->where('learning_tree_id', $learningTree->id)->get()->count() > 1;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the learning tree.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getDefaultLearningTree()
    {
        return <<<EOT
{"html":"<div class="blockelem noselect block" style="left: 363px; top: 215px; border: 2px solid; color: rgb(18, 123, 196);"><input type="hidden" name="blockelemtype" class="blockelemtype" value="1"><input type="hidden" name="blockid" class="blockid" value="0"><div class="blockyleft"><p class="blockyname"><img src="/assets/img/adapt.svg">Assessment</p></div><div class="blockydiv"></div><div class="blockyinfo">The original question.</div></div><div class="indicator invisible" style="left: 154px; top: 119px;"></div>","blockarr":[{"childwidth":318,"parent":-1,"id":0,"x":825,"y":274,"width":318,"height":109}],"blocks":[{"id":0,"parent":-1,"data":[{"name":"blockelemtype","value":"1"},{"name":"blockid","value":"0"}],"attr":[{"class":"blockelem noselect block"},{"style":"left: 363px; top: 215px; border: 2px solid; color: rgb(18, 123, 196);"}]}]}
EOT;

    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public function showByQuestion(Request $request, Question $question)
    {
        //anybody who is logged in can do this!
        $response['type'] = 'error';
        try {
            $learning_tree = $this->getLearningTreeByUserAndQuestionId(Auth::user()->id, $question->id);

            if ($learning_tree->isEmpty()) {
                $learning_tree = $this->getDefaultLearningTreeByQuestionId($question->id);
            }

            if ($learning_tree->isEmpty()) {
                $learning_tree = $this->getDefaultLearningTree();
            }

            $response['type'] = 'success';
            $response['learning_tree'] = $learning_tree;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the learning tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }


    /**
     * @param Request $request
     * @param LearningTree $learningTree
     * @param LearningTreeHistory $learningTreeHistory
     * @return array
     * @throws Exception
     */
    public function destroy(Request             $request,
                            LearningTree        $learningTree,
                            LearningTreeHistory $learningTreeHistory): array
    {
        //anybody who is logged in can do this!
        $response['type'] = 'error';
        $authorized = Gate::inspect('destroy', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $message = $this->learningTreeInAssignment($request, $learningTree, 'delete it');
            if ($message) {
                $response['message'] = $message;
                return $response;
            }
            DB::beginTransaction();
            $learningTree->learningTreeHistories()->delete();
            DB::table('branches')->where('learning_tree_id', $learningTree->id)
                ->where('user_id', $request->user()->id)
                ->delete();
            DB::table('learning_tree_node_learning_outcome')
                ->where('learning_tree_id', $learningTree->id)
                ->delete();
            $learningTree->delete();
            DB::commit();
            $response['type'] = 'info';
            $response['message'] = "The Learning Tree has been deleted.";

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting the learning Tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param LearningTree $learningTree
     * @param string $action
     * @return string
     */
    public function learningTreeInAssignment(Request $request, learningTree $learningTree, string $action): string
    {

        if ($request->user()->isAdminWithCookie()) {
            // return false;
        }
        $assignment_learning_tree_info = DB::table('assignment_question_learning_tree')->where('learning_tree_id', $learningTree->id)
            ->first();

        if (!$assignment_learning_tree_info) {
            return '';
        }

        $assignment_info = DB::table('assignment_question_learning_tree')
            ->join('assignment_question', 'assignment_question_id', '=', 'assignment_question.id')
            ->join('assignments', 'assignment_id', '=', 'assignments.id')
            ->join('courses', 'course_id', '=', 'courses.id')
            ->join('users', 'user_id', '=', 'users.id')
            ->where('assignment_question_id', $assignment_learning_tree_info->assignment_question_id)
            ->select('users.id',
                DB::raw('assignments.name AS assignment'),
                DB::raw('courses.name AS course')
            )
            ->first();


        return
            ($assignment_info->id === $request->user()->id)
                ? "It looks like you're using this Learning Tree in $assignment_info->course --- $assignment_info->assignment.  Please first remove that question from the assignment before attempting to $action."
                : "It looks like another instructor is using this Learning Tree so you won't be able to $action.";

    }

    /**
     * @param string $library
     * @param int $pageId
     * @return array
     * @throws Exception
     */
    public function validateRemediationByLibraryPageId(string $library, int $pageId): array
    {
        if (!filter_var($pageId, FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]])) {
            $response['type'] = 'error';
            $response['message'] = "$pageId should be a positive integer.";
            return $response;
        } else {
            return $this->validateLearningTreeNode($library, $pageId);
        }

    }


    /**
     * @param Request $request
     * @param string $assignmentQuestionId
     * @param int $isRootNode
     * @return array
     * @throws Exception
     */
    public function validateRemediationByAssignmentQuestionId(Request $request,
                                                              string  $assignmentQuestionId,
                                                              int     $isRootNode): array
    {
        $response['type'] = 'error';
        DB::beginTransaction();
        try {
            if ($assignmentQuestionId === '0') {
                $new_question = Question::where('title', 'Empty Learning Tree Node')->first()->toArray();
                unset($new_question['id']);
                $new_question['question_editor_user_id'] = $request->user()->id;
                $new_question['page_id'] = Question::max('page_id') + $request->user()->id;
                $new_question['public'] = 0;
                $new_question['author'] = "{$request->user()->first_name} {$request->user()->last_name}";
                $saved_questions_folder = DB::table('saved_questions_folders')
                    ->where('type', 'my_questions')
                    ->where('user_id', auth()->user()->id)
                    ->orderBy('id')
                    ->first();
                if (!$saved_questions_folder) {
                    $saved_questions_folder = new SavedQuestionsFolder();
                    $saved_questions_folder->user_id = $request->user()->id;
                    $saved_questions_folder->name = 'Main';
                    $saved_questions_folder->type = 'my_questions';
                    $saved_questions_folder->save();
                }
                $new_question['folder_id'] = $saved_questions_folder->id;
                $question = Question::create($new_question);
                $question->page_id =  $question->id;
                $question->save();
                $emptyLearningTreeNode = new EmptyLearningTreeNode();
                $emptyLearningTreeNode->question_id = $question->id;
                $emptyLearningTreeNode->save();
                Storage::disk('s3')->put("adapt/$question->id.php", 'Empty Learning Tree Node');
            } else {
                $id_type = strpos($assignmentQuestionId, "-") !== false ? 'ADAPT' : 'Question';
                $question_id = $id_type === 'ADAPT'
                    ? substr($assignmentQuestionId, strpos($assignmentQuestionId, "-") + 1)
                    : $assignmentQuestionId;

                $question = Question::find($question_id);
                if (!$question) {
                    $response['message'] = "There is no question associated with $id_type ID $assignmentQuestionId.";
                    DB::rollback();
                    return $response;
                }
                if ($isRootNode && $question->technology === 'text') {
                    $response['message'] = "The root node in the assessment should have an auto-graded technology.";
                    DB::rollback();
                    return $response;
                }
            }
            DB::commit();
            $response['question'] = $question;
            $response['type'] = 'success';
        } catch (Exception $e) {
            if (DB::transactionLevel()){
                DB::rollback();
            }
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error validating this node by assignment and question ID.";
        }
        return $response;

    }


    /**
     * @param string $library
     * @param int $pageId
     * @return array
     * @throws Exception
     */
    public function validateLearningTreeNode(string $library, int $pageId): array
    {

        $response['type'] = 'error';
        try {
            if ($library === 'adapt') {
                $question = Question::where('library', 'adapt')->where('page_id', $pageId)->first();
                if (!$question) {
                    $response['message'] = "We were not able to validate this Learning Tree node.  Please double check your library and page id or contact us for assistance.";
                    return $response;
                }
                $response['body'] = 'not sure what do to here';
                $response['title'] = $this->shortenTitle($question->title);
            } else {
                $Libretext = new Libretext(['library' => $library]);
                $contents = $Libretext->getContentsByPageId($pageId);
                $response['body'] = $contents['body'];
                $response['title'] = $contents['@title'] ?? 'Title';
                $response['title'] = $this->shortenTitle(str_replace('"', '&quot;', $response['title']));
            }
            $response['type'] = 'success';
        } catch (Exception $e) {

            if (strpos($e->getMessage(), '403 Forbidden') === false) {
                //some other error besides forbidden
                $h = new Handler(app());
                $h->report($e);
                $response['message'] = "We were not able to validate this Learning Tree node.  Please double check your library and page id or contact us for assistance.";
                return $response;
            } else {
                try {
                    $contents = $Libretext->getPrivatePage('contents', $pageId);
                    $title = '@title';
                    $response['title'] = $contents->{$title} ?? 'Title';
                    $response['title'] = $this->shortenTitle(str_replace('"', '&quot;', $response['title']));
                    $response['body'] = $contents->body[0];
                    $response['type'] = 'success';
                } catch (Exception $e) {
                    $h = new Handler(app());
                    $h->report($e);
                    $response['message'] = "We were not able to validate this Learning Tree node.  Please double check your library and page id or contact us for assistance.";

                }
            }
        }
        return $response;
    }
}
