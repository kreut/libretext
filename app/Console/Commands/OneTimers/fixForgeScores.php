<?php

namespace App\Console\Commands\OneTimers;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Score;
use App\SubmissionFile;
use App\User;
use Exception;
use Illuminate\Console\Command;
use App\Http\Controllers\ScoreController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class fixForgeScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:forgeScores';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            DB::beginTransaction();
            $course_id = 7361;
            $course = Course::find($course_id);
            $assignments = $course->assignments;
            $assignment_ids = SubmissionFile::whereIn('assignment_id', $assignments->pluck('id'))
                ->where('type', 'forge')
                ->distinct()
                ->pluck('assignment_id');
            $user = User::find($course->user_id);

            foreach ($assignment_ids as $assignment_id) {
                $mismatches = [];
                $current_assignment_scores_by_user_id = [];
                $current_assignment_scores = DB::table('scores')->where('assignment_id', $assignment_id)->get();
                foreach ($current_assignment_scores as $current_assignment_score) {
                    $current_assignment_scores_by_user_id[$current_assignment_score->user_id] = +$current_assignment_score->score;
                }

                $assignment = Assignment::find($assignment_id);
                $data = $this->getAssignmentQuestionScoresByUser($user, $assignment, 'hidden', new Score(), new Enrollment(), 0);
                foreach ($data['rows'] as $row) {
                    if (!DB::table('submission_files')->where('assignment_id', $assignment->id)
                        ->where('user_id', $row['userId'])
                        ->whereNotNull('score')
                        ->exists()) {
                        continue;
                    }
                    if (abs($row['total_points'] - $current_assignment_scores_by_user_id[$row['userId']]) > 0.0001) {
                        $num_updated = Score::where('assignment_id', $assignment->id)
                            ->where('user_id', $row['userId'])
                            ->update(['score' => $row['total_points']]);
                        DB::table('fix_forge_scores')->insert([
                            'assignment_id' => $assignment->id,
                            'user_id' => $row['userId'],
                            'score' => $row['total_points']
                        ]);
                        $mismatches[] = [
                            'name' => $row['name'],
                            'user_id' => $row['userId'],
                            'expected' => $row['total_points'],
                            'actual' => $current_assignment_scores_by_user_id[$row['userId']],
                            'diff' => $row['total_points'] - $current_assignment_scores_by_user_id[$row['userId']],
                            'num_updated' => $num_updated
                        ];

                    }
                }
                $this->info($assignment->name);
                $this->table(['Name', 'User ID', 'Expected', 'Actual', 'Diff'], $mismatches);
            }
            DB::commit();
            $this->info('Done!');
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }

        return 0;
    }

    public
    function getAssignmentQuestionScoresByUser(User       $user,
                                               Assignment $assignment,
                                               string     $time_spent_option,
                                               Score      $score,
                                               Enrollment $enrollment,
                                               int        $download): array
    {

        $response['type'] = 'error';

        try {
            $enrolled_users = [];
            $viewable_users = $enrollment->getEnrolledUsersByRoleCourseSection($user->role, $assignment->course, 0);


            if ($viewable_users->isNotEmpty()) {
                $sorted_users = [];
                $assign_to_timings_by_user = $assignment->assignToTimingsByUser();
                foreach ($viewable_users as $key => $viewable_user) {
                    if (!isset($assign_to_timings_by_user[$viewable_user->id])) {
                        unset($viewable_users [$key]);
                    }
                }
                foreach ($viewable_users as $value) {
                    $sorted_users[] = ['name' => "$value->last_name, $value->first_name",
                        'id' => $value->id,
                        'email' => $value->email,
                        'student_id' => $value->student_id];
                }


                usort($sorted_users, function ($a, $b) {
                    return $a['name'] <=> $b['name'];
                });
                $sorted_users_by_user_id = [];
                foreach ($sorted_users as $value) {
                    $enrolled_users[$value['id']] = $value['name'];
                    $sorted_users_by_user_id[$value['id']] = $value;
                }
            }
            $submission_score_overrides_by_assignment = DB::table('submission_score_overrides')
                ->where('assignment_id', $assignment->id)
                ->get();

            foreach ($submission_score_overrides_by_assignment as $value) {
                $submission_score_override[$value->user_id][$value->question_id] = $value->score;

            }

            $file_submission_scores = [];
            foreach ($assignment->fileSubmissions as $value) {
                $file_submission_scores[$value->user_id][$value->question_id] = $value->score;
            }
            $submission_scores = [];
            foreach ($assignment->submissions as $value) {
                $submission_scores[$value->user_id][$value->question_id] = $value->score;
            }
            $points = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->select('question_id', 'points')
                ->get();
            $total_points = 0;
            foreach ($points as $key => $value) {
                $total_points += $value->points;
                $total_points_by_question_id[$value->question_id] = Helper::removeZerosAfterDecimal(round((float)$value->points, 2));
            }

            $questions = $assignment->questions;
            $rows = [];
            $time_on_tasks = DB::table('assignment_question_time_on_tasks')
                ->where('assignment_id', $assignment->id)
                ->get();
            $time_on_tasks_by_user_question = [];
            foreach ($time_on_tasks as $time_on_task) {
                $time_on_tasks_by_user_question[$time_on_task->user_id][$time_on_task->question_id] = $time_on_task->time_on_task;
            }

            $time_in_reviews = DB::table('review_histories')
                ->where('assignment_id', $assignment->id)
                ->select('user_id', 'assignment_id', 'question_id', DB::RAW('TIMESTAMPDIFF(SECOND, created_at, updated_at) AS time_in_review'))
                ->get();
            $time_in_reviews_by_user_question = [];
            foreach ($time_in_reviews as $time_in_review) {
                $time_in_reviews_by_user_question[$time_in_review->user_id][$time_in_review->question_id] =
                    isset($time_in_reviews_by_user_question[$time_in_review->user_id][$time_in_review->question_id])
                        ? $time_in_reviews_by_user_question[$time_in_review->user_id][$time_in_review->question_id] + $time_in_review->time_in_review
                        : $time_in_review->time_in_review;
            }
            $scores = DB::table('scores')->where('assignment_id', $assignment->id)->get();
            $scores_by_user_id = [];
            foreach ($scores as $score) {
                $scores_by_user_id[$score->user_id] = $score->score;
            }

            $submission_score_overrides = [];
            $original_submission_scores = [];

            foreach ($enrolled_users as $user_id => $name) {
                $columns = [];
                $assignment_score = 0;
                foreach ($questions as $question) {
                    $submitted_something = isset($submission_scores[$user_id][$question->id]) || isset($file_submission_scores[$user_id][$question->id]);
                    $has_submission_score_override = isset($submission_score_override[$user_id][$question->id]);
                    $score = '-';
                    if ($submitted_something) {
                        $score = 0;
                        $score = $score
                            + ($submission_scores[$user_id][$question->id] ?? 0)
                            + ($file_submission_scores[$user_id][$question->id] ?? 0);
                        $original_submission_scores[] = ['user_id' => $user_id, 'question_id' => $question->id, 'original_score' => $score];
                    }
                    if ($has_submission_score_override) {
                        $score = $submission_score_override[$user_id][$question->id] ?? 0;
                        $submission_score_overrides[] = ['user_id' => $user_id, 'question_id' => $question->id];
                    }
                    if ($submitted_something || $has_submission_score_override) {

                        $assignment_score += $score;
                    }

                    $time_spent = '';
                    if ($submitted_something) {
                        switch ($time_spent_option) {
                            case('hidden'):
                                break;
                            case('on_task'):
                                $time_spent = $this->formatTimeSpent($time_on_tasks_by_user_question, $user_id, $question->id);
                                break;
                            case('in_review'):
                                $time_spent = $this->formatTimeSpent($time_in_reviews_by_user_question, $user_id, $question->id);
                                break;
                            case('default'):
                                throw new Exception("$time_spent_option is not a valid time spent option.");
                        }
                    }
                    $score = $score === '-' ? $score : Helper::removeZerosAfterDecimal(round((float)$score, 2));
                    $columns[$question->id] = $score . ' ' . $time_spent;
                }
                $columns['name'] = $name;

                if ($total_points) {
                    $columns['percent_correct'] = 100 * Helper::removeZerosAfterDecimal(round((float)$assignment_score / $total_points, 4)) . '%';
                    $columns['total_points'] = Helper::removeZerosAfterDecimal(round((float)$assignment_score, 2));
                } else {
                    $columns['percent_correct'] = "N/A";
                    $columns['total_points'] = 0;
                }
                if (isset($scores_by_user_id[$user_id])) {
                    $assignment_score = Helper::removeZerosAfterDecimal(round((float)$scores_by_user_id[$user_id], 2));
                    if ($assignment_score !== $columns['total_points']) {
                        $columns['override_score'] = $assignment_score;
                    }
                }
                $columns['userId'] = $user_id;
                $columns['email'] = $sorted_users_by_user_id[$user_id]['email'] ?? 'None provided';
                $columns['student_id'] = $sorted_users_by_user_id[$user_id]['student_id'] ?? 'None provided';
                $rows[] = $columns;

            }

            $fields = [['key' => 'name',
                'label' => 'Name',
                'sortable' => true,
                'isRowHeader' => true,
                'stickyColumn' => true,
                'thStyle' => 'max-width: 100px']];

            $i = 1;
            foreach ($questions as $question) {
                $points = $total_points_by_question_id[$question->id];
                $field = ['key' => "$question->id",
                    'isRowHeader' => true,
                    'label' => "Q$i ($points)",
                    'sortable' => true];
                $i++;
                $fields[] = $field;
            }

            if ($total_points) {
                $fields[] = ['key' => 'total_points',
                    'sortable' => true,
                    'isRowHeader' => true];
                $fields[] = ['key' => 'percent_correct',
                    'sortable' => true,
                    'isRowHeader' => true];
            }

            if ($download) {
                $download_rows = [];
                $download_row = [];
                foreach ($fields as $field) {
                    $download_row[] = $field['label'] ?? ucwords(str_replace('_', ' ', $field['key']));
                }
                $download_rows[0] = $download_row;
                foreach ($rows as $row) {
                    $download_row = [];
                    foreach ($fields as $field) {
                        $download_row [] = $row[$field['key']];
                    }
                    $download_rows[] = $download_row;
                }
                Helper::arrayToCsvDownload($download_rows, "$assignment->name.csv");
                exit;
            }
            $response['type'] = 'success';
            $response['rows'] = $rows;
            $response['fields'] = $fields;
            $response['submission_score_overrides'] = $submission_score_overrides;
            $response['original_submission_scores'] = $original_submission_scores;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the scores for each question.  Please try again or contact us for assistance.";

        }

        return $response;


    }
}
