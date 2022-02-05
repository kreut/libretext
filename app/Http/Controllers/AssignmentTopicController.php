<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentTopic;
use App\Course;
use App\Exceptions\Handler;
use App\Http\Requests\StoreAssignmentTopic;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PhpParser\Node\Expr\Assign;

class AssignmentTopicController extends Controller
{
    /**
     * @param StoreAssignmentTopic $request
     * @param AssignmentTopic $assignmentTopic
     * @return array
     * @throws Exception
     */
    public
    function store(StoreAssignmentTopic $request, AssignmentTopic $assignmentTopic): array
    {
        $response['type'] = 'error';
        $assignment_id = $request->assignment_id;
        $assignment = Assignment::find($assignment_id);

        if (!$assignment) {
            $response['message'] = "We could not find an assignment with the ID $request->assignment_id. Please try again or contact us.";
            return $response;
        }
        $authorized = Gate::inspect('store', [$assignmentTopic, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            $data = $request->validated();
            $assignmentTopic->name = $data['name'];
            $assignmentTopic->assignment_id = $assignment_id;
            $assignmentTopic->save();

            $response['topic_id'] = $assignmentTopic->id;
            $response['type'] = 'success';
            $response['message'] = "{$data['name']} has been created.";
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating <strong>$request->name</strong>.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    /**
     * @param StoreAssignmentTopic $request
     * @param AssignmentTopic $assignmentTopic
     * @return array
     * @throws Exception
     */
    public
    function update(StoreAssignmentTopic $request, AssignmentTopic $assignmentTopic): array
    {

        $authorized = Gate::inspect('update', [$assignmentTopic, $request->topic_id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            $data = $request->validated();
            $assignmentTopic->where('id', $request->topic_id)->update(['name' => $data['name']]);
            $response['message'] = "The topic's name has been updated to {$data['name']}.";
            $response['type'] = 'success';
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the topic to <strong>$request->name</strong>.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param AssignmentTopic $assignmentTopic
     * @return array
     * @throws Exception
     */
    public
    function move(Request         $request,
                  Assignment      $assignment,
                  AssignmentTopic $assignmentTopic): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('move', [$assignmentTopic,
            $assignment->id,
            $request->question_ids_to_move,
            $assignmentTopic->id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->whereIn('question_id', $request->question_ids_to_move)
                ->update(['assignment_topic_id' => $assignmentTopic->id]);
            $verb = count($request->question_ids_to_move) > 1 ? 's have' : ' has';
            $response['message'] = "The question$verb been moved to $assignmentTopic->name.";
            $response['type'] = 'success';
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error moving the question.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    public
    function delete(Request $request, AssignmentTopic $assignmentTopic): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('delete', $assignmentTopic);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $questions_exist = DB::table('assignment_question')
                ->where('assignment_topic_id', $assignmentTopic->id)
                ->first();
            if ($questions_exist && $request->move_to_topic_id !== null) {
                $move_to_topic = $assignmentTopic->where('id', $request->move_to_topic_id)->first();
                if ($move_to_topic->assignment_id !== $assignmentTopic->assignment_id) {
                    $response['message'] = "You cannot move the topic's questions to a topic in a different assignment.";
                    return $response;
                }
            }
            DB::beginTransaction();
            DB::table('assignment_question')
                ->where('assignment_topic_id', $assignmentTopic->id)
                ->update(['assignment_topic_id' => $request->move_to_topic_id]);
            $deleted_topic_name = $assignmentTopic->name;
            $questions_message = '';
            if ($questions_exist) {
                if ($request->move_to_topic_id !== null) {

                    $move_to_topic_name = $move_to_topic->name;
                    $move_to_assignment_id = $move_to_topic->assignment_id;
                    $questions_message = "All questions from $deleted_topic_name have been moved to $move_to_topic_name.";
                } else {
                    $move_to_assignment_id = $assignmentTopic->assignment_id;
                    $questions_message = "All questions from $deleted_topic_name have been moved to the base assignment.";
                }
            } else {
                $move_to_assignment_id = $assignmentTopic->assignment_id;
            }
            $assignmentTopic->delete();
            $response['message'] = "$deleted_topic_name has been deleted.  ";
            if ($questions_exist) {
                $response['message'] .= $questions_message;
            }
            $move_to_topic_num_questions = DB::table('assignment_question')
                ->where('assignment_topic_id', $request->move_to_topic_id)
                ->count();
            $move_to_topic_assignment_questions = DB::table('assignment_question')
                ->where('assignment_id', $move_to_assignment_id)
                ->count();
            $response['move_to_assignment_id'] = $move_to_assignment_id;
            $response['move_to_topic_num_questions'] = $move_to_topic_num_questions;
            $response['move_to_assignment_num_questions'] = $move_to_topic_assignment_questions;
            $response['type'] = 'info';
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting the topic.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    /**
     * @param Course $course
     * @param AssignmentTopic $assignmentTopic
     * @return array
     * @throws Exception
     */
    public function getAssignmentTopicsByCourse(Course $course, AssignmentTopic $assignmentTopic): array
    {


        $response['type'] = 'error';
        $authorized = Gate::inspect('getAssignmentTopicsByCourse', $assignmentTopic);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $topics = DB::table('assignment_topics')
                ->whereIn('assignment_id', $course->assignments->pluck('id')->toArray())
                ->select('name', 'id', 'assignment_id')
                ->orderBy('name')
                ->get();
            $response['topics'] = $topics;
            $response['type'] = 'success';
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was getting the assignment topics.  Please try again or contact us for assistance.";
        }

        return $response;


    }

    /**
     * @param Assignment $assignment
     * @param AssignmentTopic $assignmentTopic
     * @return array
     * @throws Exception
     */
    public function getAssignmentTopicsByAssignment(Assignment $assignment,
                                                    AssignmentTopic $assignmentTopic): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAssignmentTopicsByAssignment', $assignmentTopic);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $topics = DB::table('assignment_topics')
                ->leftJoin('assignment_question','assignment_topics.id','=','assignment_question.assignment_topic_id')
                ->where('assignment_topics.assignment_id', $assignment->id)
                ->select('name',
                    'assignment_topics.id',
                    'assignment_topics.assignment_id',
                    DB::raw('COUNT(assignment_question.question_id) AS num_questions'))
                ->groupBY('assignment_topics.id')
                ->orderBy('name')
                ->get();
            $response['topics'] = $topics;
            $response['type'] = 'success';
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was getting the assignment topics.  Please try again or contact us for assistance.";
        }

        return $response;


    }


}
