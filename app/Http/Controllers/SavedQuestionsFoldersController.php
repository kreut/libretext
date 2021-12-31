<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Http\Requests\StoreSavedQuestionsFolder;

use App\SavedQuestion;
use App\SavedQuestionsFolder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SavedQuestionsFoldersController extends Controller
{
    public function index(Request $request)
    {

        $response['type'] = 'error';
        try {
            $response['saved_questions_folders'] = DB::table('saved_questions_folders')
                ->leftJoin('saved_questions','saved_questions_folders.id','=','saved_questions.folder_id')
                ->where('saved_questions_folders.user_id', $request->user()->id)
                ->select('saved_questions_folders.id', 'name',  DB::raw('count(saved_questions.id) as num_questions'))
                ->groupBy('id')
                ->get();

            $response['type'] = 'success';
        } catch (Exception $e) {
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

        try {
            $data = $request->validated();
            $savedQuestionsFolder->name = $data['name'];
            $savedQuestionsFolder->user_id = $request->user()->id;
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
        $original_name =  $savedQuestionsFolder->name;
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
    function destroy(Request $request, SavedQuestionsFolder $savedQuestionsFolder, SavedQuestion $savedQuestion): array
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
        $message = "That is not a valid deleting option.";
        try {
            switch ($request->action) {
                case('move'):
                    $current_folder_questions = $savedQuestion->where('folder_id', $savedQuestionsFolder->id)->get();
                    foreach ($current_folder_questions as $current_folder_question) {
                        if (!$savedQuestion
                            ->where('folder_id', $request->move_to_folder_id)
                            ->where('question_id', $current_folder_question->question_id)
                            ->exists()) {
                            $current_folder_question->folder_id = $request->move_to_folder_id;
                            $current_folder_question->save();
                        }
                    }
                    $move_to_folder_name = SavedQuestionsFolder::find($request->move_to_folder_id)->name;
                    $message = "The folder $savedQuestionsFolder->name has been deleted and all questions have been moved to $move_to_folder_name.";
                    break;
                case('delete_without_moving'):
                    $message = "The folder $savedQuestionsFolder->name has been deleted along with all question in that folder.";
                    $savedQuestion->where('folder_id', $savedQuestionsFolder->id)->delete();
                    break;
                default:
                    $response['message'] = $message;
                    return $response;
            }
            $savedQuestionsFolder->delete();
            $response['type'] = 'info';
            $response['message'] = $message;
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating the folder.  Please try again or contact us for assistance.";
        }
        return $response;
    }


}
