<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\LearningTreeAnalytics;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Gate;

class LearningTreeAnalyticsController extends Controller
{
    public function index(LearningTreeAnalytics $learningTreeAnalytics)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('index', $learningTreeAnalytics);

        $rows = [];
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $learning_tree_analytics = LearningTreeAnalytics::get();
            $response['type'] = 'success';
            $columns = ['Course Name', 'Instructor', 'Assignment Name', 'User ID','Learning Tree ID', 'Question ID', 'Root Node', 'Action', 'Response', 'Created At'];
            $rows[0] = $columns;
            $keys = ['course_name', 'instructor', 'assignment_name', 'user_id','learning_tree_id', 'question_id', 'root_node', 'action', 'response', 'created_at'];
            foreach ($learning_tree_analytics as $data) {
                $values = [];
                foreach ($keys as $key) {
                    $values[] = $data->{$key};
                }
                $rows[] = $values;
            }
            $date = Carbon::now()->format('Y-m-d');
            Helper::arrayToCsvDownload($rows, "learning-tree-analytics-$date");

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to retrieve the Learning Tree Analytics.  Please try again.";
            return $response;
        }
    }
}
