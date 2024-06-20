<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class Analytics
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Parse the token
            $token = $request->bearerToken();
            if ($token) {
                $payload = \JWTAuth::getJWTProvider()->decode($token);
                if (isset($payload['analytics']) && $payload['analytics']) {
                    $method = $request->method();

                    $action_name = str_replace("App\Http\Controllers\\", '', app()->router->getCurrentRoute()->getActionName());

                    $valid_action_names = ['FrameworkController@index',
                        'FrameworkLevelController@getFrameworkLevelChildren',
                        'FrameworkLevelController@getAllChildren',
                        'FrameworkItemSyncQuestionController@getFrameworkItemsByQuestion',
                        'FinalGradeController@getCourseLetterGrades',
                        'AssignmentController@index',
                        'AssignmentController@getQuestionsInfo',
                        'AssignmentController@getAssignmentSummary',
                        'ScoreController@getScoresByAssignmentAndQuestion',
                        'ScoreController@getCourseScoresByUser',
                        'ScoreController@getScoreByAssignmentAndStudent',
                        'EnrollmentController@details',
                        'AssignmentQuestionTimeOnTaskController@getTimeOnTasksByAssignment',
                        'ScoreController@getAssignmentQuestionScoresByUser',
                        'AutoGradedAndFileSubmissionController@getSubmissionTimesByAssignmentAndStudent'
                    ];

                    if ($method !== 'GET' || !in_array($action_name, $valid_action_names)) {
                        return response()->json([
                            'type' => 'error',
                            'message' => "$method:$action_name is not authorized for analytics use."], 401);
                    }
                }
            }
        } catch (JWTException $e) {
            return response()->json(['type' => 'error',
                'message' => 'Token error'], 401);
        }

        return $next($request);
    }
}
