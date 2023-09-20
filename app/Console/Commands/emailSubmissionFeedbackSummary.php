<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Snowfire\Beautymail\Beautymail;
use Throwable;

class emailSubmissionFeedbackSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:submissionFeedbackSummary';

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
     * @return int
     * @throws Throwable
     */
    public function handle(): int
    {
        try {
            $submission_files = DB::table('submission_files')
                ->join('assignment_question', function ($join) {
                    $join->on('submission_files.assignment_id', '=', 'assignment_question.assignment_id')
                        ->on('submission_files.question_id', '=', 'assignment_question.question_id');
                })
                ->join('users', 'submission_files.user_id', '=', 'users.id')
                ->join('assignments', 'submission_files.assignment_id', '=', 'assignments.id')
                ->where('date_graded', '>=', Carbon::now()->subDay())
                ->where('date_graded', '<=', Carbon::now())
                ->select('assignment_question.order',
                    'assignment_question.question_id',
                    'assignment_question.assignment_id',
                    'submission_files.user_id',
                    'submission_files.text_feedback',
                    'submission_files.file_feedback',
                    'users.email',
                    'assignments.name AS assignment_name')
                ->get();
            $submission_files_with_feedback = [];
            foreach ($submission_files as $submission_file) {
                if ($submission_file->text_feedback || $submission_file->file_feedback) {
                    if (!isset($submission_files_with_feedback[$submission_file->user_id])) {
                        $submission_files_with_feedback[$submission_file->user_id] = [
                            'email' => $submission_file->email
                        ];
                    }
                    if (!isset($submission_files_with_feedback[$submission_file->user_id][$submission_file->assignment_name]))
                        $submission_files_with_feedback[$submission_file->user_id]['feedback_info'][$submission_file->assignment_name] = [];
                    $submission_files_with_feedback[$submission_file->user_id]['feedback_info'][$submission_file->assignment_name][] = [
                        'assignment_id' => $submission_file->assignment_id,
                        'question_id' => $submission_file->question_id,
                        'order' => $submission_file->order,
                        'text_feedback' => $submission_file->text_feedback,
                        'file_feedback' => $submission_file->file_feedback
                            ? Storage::disk('s3')->temporaryUrl("assignments/$submission_file->assignment_id/$submission_file->file_feedback", Carbon::now()->addDays(7))
                            : null
                    ];
                }
            }

            $beauty_mail = app()->make(Beautymail::class);
            foreach ($submission_files_with_feedback as $submission_file_with_feedback) {
                $to_email = $submission_file_with_feedback['email'];
                $feedback_infos = $submission_file_with_feedback['feedback_info'];
                $formatted_text = '';
                foreach($feedback_infos as $assignment=>$question_infos){
                    $formatted_text = "<div style='padding-bottom:13px;'><span style='font-weight:bold'>$assignment</span><br><br>";
                    foreach ($question_infos as $question_info) {
                        $formatted_text .= "<div style='padding-left:5px;padding-bottom:20px'>";
                        $question_url = env('APP_URL') . "/assignments/{$question_info['assignment_id']}/questions/view/{$question_info['question_id']}";
                        $formatted_text .= "<a href='$question_url' target='_blank' style='color:#0058e6'>Question #{$question_info['order']}</a>";
                        $formatted_text .= "<div style='padding-left:5px'>";
                        if ($question_info['text_feedback']) {
                            $formatted_text .= "{$question_info['text_feedback']}<br>";
                        }
                        if ($question_info['file_feedback']) {
                            $formatted_text .= "<a href='{$question_info['file_feedback']}' target='_blank' style='color:#0058e6'>View Feedback</a><br>";
                        }
                        $formatted_text .= "</div></div>";
                    }
                    $formatted_text .= "</div>";

                }
                $beauty_mail->send('emails.submission_file_feedback',['formatted_text' => $formatted_text], function ($message)
                use ($to_email) {
                    $message
                        ->from('adapt@noreply.libretexts.org', 'ADAPT')
                        ->to($to_email)
                        ->subject('Submission Feedback');
                });

            }

        } catch (Exception $e) {


            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        return 0;
    }
}
