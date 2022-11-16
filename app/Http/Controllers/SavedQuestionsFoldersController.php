<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Http\Requests\StoreSavedQuestionsFolder;

use App\MyFavorite;
use App\Question;
use App\SavedQuestionsFolder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SavedQuestionsFoldersController extends Controller
{
    /**
     * @param Request $request
     * @param SavedQuestionsFolder $savedQuestionsFolder
     * @return array
     * @throws Exception
     */
    public function getClonedQuestionsFolder(Request              $request,
                                             SavedQuestionsFolder $savedQuestionsFolder): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getClonedQuestionsFolder', $savedQuestionsFolder);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $cloned_questions_folder = $savedQuestionsFolder->where('name', 'Cloned questions')
                ->where('user_id', $request->user()->id)
                ->first();
            if (!$cloned_questions_folder) {
                $savedQuestionsFolder = new SavedQuestionsFolder();
                $savedQuestionsFolder->name = 'Cloned questions';
                $savedQuestionsFolder->type = 'my_questions';
                $savedQuestionsFolder->user_id = $request->user()->id;
                $savedQuestionsFolder->save();
                $cloned_questions_folder_id = $savedQuestionsFolder->id;
            } else {
                $cloned_questions_folder_id = $cloned_questions_folder->id;
            }
            $response['type'] = 'success';
            $response['cloned_questions_folder_id'] = $cloned_questions_folder_id;
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your Cloned questions folder.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param SavedQuestionsFolder $savedQuestionsFolder
     * @return array
     * @throws Exception
     */
    public function getMyQuestionsFoldersAsOptions(Request              $request,
                                                   SavedQuestionsFolder $savedQuestionsFolder): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getMyQuestionsFoldersAsOptions', $savedQuestionsFolder);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $my_questions_folders = DB::table('saved_questions_folders')
                ->select('id', 'name')
                ->where('saved_questions_folders.user_id', $request->user()->id)
                ->where('type', 'my_questions')
                ->get();
            $my_questions_folders = $savedQuestionsFolder->getMyQuestionsFoldersWithH5pImportsAndTransferredQuestionsFirst($my_questions_folders);
            $response['type'] = 'success';
            $response['my_questions_folders'] = $my_questions_folders;
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your My Questions Folder.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param String $type
     * @param SavedQuestionsFolder $savedQuestionsFolder
     * @param Question $question
     * @param int $with_h5p
     * @return array
     * @throws Exception
     */
    public function getSavedQuestionsFoldersByType(Request              $request,
                                                   string               $type,
                                                   SavedQuestionsFolder $savedQuestionsFolder,
                                                   Question             $question,
                                                   int                  $withH5P = 0): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getSavedQuestionsFoldersByType', $savedQuestionsFolder);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        if (!in_array($type, ['my_favorites', 'my_questions'])) {
            $response['message'] = "$type is not a valid type.";
            return $response;
        }
        switch ($type) {
            case('my_favorites'):
                $questions_table = 'my_favorites';
                break;
            case('my_questions'):
                if ($withH5P) {
                    $h5p_responses = $question->autoImportH5PQuestions();
                    foreach ($h5p_responses as $key => $value) {
                        if ($key !== 'type') {
                            $response[$key] = $value;
                        }
                        if ($key === 'type' && $value === 'error' && isset($h5p_responses['message'])) {
                            $response['message'] = $h5p_responses['message'];
                            return $response;
                        }
                    }
                }
                $questions_table = 'questions';
                $empty_learning_tree_nodes = DB::table('empty_learning_tree_nodes')
                    ->join('questions', 'empty_learning_tree_nodes.question_id', '=', 'questions.id')
                    ->select('folder_id', DB::raw("COUNT(questions.id) as num_questions"))
                    ->groupBy('folder_id')
                    ->get();
                $empty_learning_tree_nodes_by_folder_id = [];
                foreach ($empty_learning_tree_nodes as $empty_learning_tree_node) {
                    $empty_learning_tree_nodes_by_folder_id[$empty_learning_tree_node->folder_id] = $empty_learning_tree_node->num_questions;
                }
                break;
            default:
                $questions_table = '';
        }
        try {
            $saved_questions_folders = DB::table('saved_questions_folders')
                ->leftJoin($questions_table, 'saved_questions_folders.id', '=', "$questions_table.folder_id")
                ->where('saved_questions_folders.user_id', $request->user()->id)
                ->where('saved_questions_folders.type', $type);

            $saved_questions_folders = $saved_questions_folders
                ->select('saved_questions_folders.id', 'name', DB::raw("COUNT($questions_table.id) as num_questions"))
                ->groupBy('id')
                ->get();

            if ($type === 'my_questions') {
                foreach ($saved_questions_folders as $key => $saved_questions_folder) {
                    if (isset($empty_learning_tree_nodes_by_folder_id[$saved_questions_folder->id])) {
                        $saved_questions_folders[$key]->num_questions = $saved_questions_folders[$key]->num_questions - $empty_learning_tree_nodes_by_folder_id[$saved_questions_folder->id];
                    }
                }
            }

            if ($saved_questions_folders->isEmpty()) {
                $savedQuestionsFolder = new SavedQuestionsFolder();
                $savedQuestionsFolder->user_id = $request->user()->id;
                $savedQuestionsFolder->name = 'Main';
                $savedQuestionsFolder->type = $type;
                $savedQuestionsFolder->save();
                $saved_questions_folders = [$savedQuestionsFolder];
            }
            if ($type === 'my_questions') {
                $saved_questions_folders = $savedQuestionsFolder->getMyQuestionsFoldersWithH5pImportsAndTransferredQuestionsFirst($saved_questions_folders);

            }

            $response['saved_questions_folders'] = $saved_questions_folders;
            $response['type'] = 'success';
        } catch
        (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get a list of your saved folders.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public
    function store(StoreSavedQuestionsFolder $request, SavedQuestionsFolder $savedQuestionsFolder): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $savedQuestionsFolder);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        if (!in_array($request->type, ['my_favorites', 'my_questions'])) {
            $response['message'] = "$request->type is not a valid type of folder.";
            return $response;
        }
        try {
            $data = $request->validated();
            $savedQuestionsFolder->name = $data['name'];
            $savedQuestionsFolder->user_id = $request->user()->id;
            $savedQuestionsFolder->type = $request->type;
            $savedQuestionsFolder->save();
            $response['type'] = 'success';
            $response['message'] = "The folder {$data['name']} has been created.";
            $response['folder_id'] = $savedQuestionsFolder->id;
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating the folder.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public
    function update(StoreSavedQuestionsFolder $request): array
    {
        $savedQuestionsFolder = SavedQuestionsFolder::find($request->folder_id);
        $original_name = $savedQuestionsFolder->name;
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $savedQuestionsFolder);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $data = $request->validated();
            $savedQuestionsFolder->name = $data['name'];
            $savedQuestionsFolder->user_id = $request->user()->id;
            $savedQuestionsFolder->save();
            $response['type'] = 'success';
            $response['message'] = "The folder $original_name has been updated.";
            $response['folder_id'] = $savedQuestionsFolder->id;
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the folder.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public
    function destroy(Request              $request,
                     SavedQuestionsFolder $savedQuestionsFolder,
                     MyFavorite           $myFavorite,
                     Question             $question): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('destroy', $savedQuestionsFolder);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        if ($request->action === 'move'
            && !$savedQuestionsFolder
                ->where('user_id', $request->user()->id)
                ->where('id', $request->move_to_folder_id)
                ->exists()) {
            $response['message'] = "You are trying to move the questions to a folder which you do not own.";
            return $response;
        }

        try {
            DB::beginTransaction();
            switch ($request->action) {
                case('move'):
                    $savedQuestionsFolder->moveThenDeleteFolder($request, $myFavorite, $question);
                    $move_to_folder_name = SavedQuestionsFolder::find($request->move_to_folder_id)->name;
                    $message = "The folder $savedQuestionsFolder->name has been deleted and all questions have been moved to $move_to_folder_name.";
                    break;
                case('delete_without_moving'):
                    $table = $request->question_source === 'my_questions' ? 'questions' : 'my_favorites';
                    $questions_exist = DB::table($table)
                        ->where('folder_id', $savedQuestionsFolder->id)
                        ->exists();

                    if ($request->question_source === 'my_questions' && $questions_exist) {
                        $response['message'] = "These questions must be moved.  They cannot simply be deleted.";
                        return $response;
                    }
                    $message = !$questions_exist
                        ? "The folder $savedQuestionsFolder->name has been deleted"
                        : "The folder $savedQuestionsFolder->name has been deleted along with all question in that folder.";
                    $myFavorite->where('folder_id', $savedQuestionsFolder->id)->delete();
                    break;
                default:
                    $response['message'] = "Incorrect mode of deleting folders.";
                    return $response;
            }
            $savedQuestionsFolder->delete();
            DB::commit();
            $response['type'] = 'info';
            $response['message'] = $message;
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting the folder.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public
    function move(Question             $question,
                  SavedQuestionsFolder $fromFolder,
                  SavedQuestionsFolder $toFolder): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('move', [$toFolder, $fromFolder, $question]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        if ($fromFolder->type !== $toFolder->type) {
            $response['message'] = "You are moving from a $fromFolder->type to a $toFolder->type folder: both folder types should be the same.";
            return $response;
        }

        if ($fromFolder->id === $toFolder->id) {
            $response['message'] = "You are not moving the question to a different folder.";
            return $response;
        }
        try {
            DB::beginTransaction();
            switch ($fromFolder->type) {
                case('my_favorites'):
                    DB::table('my_favorites')->where('question_id', $question->id)
                        ->where('user_id', request()->user()->id)
                        ->where('folder_id', $fromFolder->id)
                        ->update(['folder_id' => $toFolder->id]);
                    break;
                case('my_questions'):
                    DB::table('questions')
                        ->where('id', $question->id)
                        ->where('question_editor_user_id', request()->user()->id)
                        ->where('folder_id', $fromFolder->id)
                        ->update(['folder_id' => $toFolder->id]);
                    break;
                default:
                    $response['message'] = "$fromFolder->type is not a valid folder type.";
                    return $response;
            }


            $response['type'] = 'info';
            $response['message'] = "The question $question->title has been moved from $fromFolder->name to $toFolder->name.";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing the saved question.  Please try again or contact us for assistance.";
        }

        return $response;
    }


}
