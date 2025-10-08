<?php

namespace App\Console\Commands\OneTimers\QuestionSubjectChapterSection;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class createWebworkSubjectRegexMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:WebworkSujbectRegexMapping';

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
            $webwork_questions = Question::where('technology', 'webwork')
                ->whereNotNull('webwork_code')
                ->get();
            $webwork_table_subject_mappings = [];
            $lower_cases = [];
            foreach ($webwork_questions as $webwork_question) {
                $webwork_code = $webwork_question->webwork_code;
                preg_match('/## DBsubject\((.*)\)\s*$/m', $webwork_code, $subject_match);
                $original_subject = $subject_match[1] ?? null;

                if ($original_subject) {
                    $subject = $original_subject;
                    if (preg_match('/^\d+(\.\d+)?$/', $subject)) {
                        continue;
                    }
                    if ($subject === "'ZZZ-Inserted Text'") {
                        continue;
                    }
                    if ($subject === "TODO") {
                        continue;
                    }
                    if (preg_match('/^\s*\d+\s*[-:]?\s*Rosen\b/i', $subject)) {
                        continue;
                    }
                    if (preg_match('/^Chem\s+\S+:/i', $subject)) {
                        continue;
                    }


                    $subject = preg_replace("/^'|'$/", '', $subject);
                    $subject = preg_replace('/^\d+\s*:\s*/', '', $subject);
                    $subject = preg_replace('/^Chapter\s*\d+\s*:\s*/i', '', $subject);
                    $subject = trim($subject);
                    if ($subject === "Midterm") {
                        continue;
                    }
                    if ($subject === "Pre-Lab Quiz") {
                        continue;
                    }
                    if ($subject === "WeBWorK") {
                        continue;
                    }
                    if ($subject === '') {
                        continue;
                    }
                    if (preg_match('/^\[REFER/i', $subject)) {
                        continue;
                    }
                    $search = strtolower($subject);
                    if (in_array($search,array_values($lower_cases))){
                        $lowered = array_map('strtolower', $lower_cases);
                        $key = array_search($search, $lowered, true);
                        if ($key !== false) {
                            $subject = $key;
                        } else {
                            // not found
                        }
                    }
                    $lower_cases[$subject] = $search;
                    //if ($original_subject !== $subject) {
                    //if (!in_array($subject, $webwork_table_subject_mappings)) {

                    if (!DB::table('webwork_regex_subject_mappings')->where('question_id', $webwork_question->id)->first()) {
                        DB::table('webwork_regex_subject_mappings')
                            ->insert(['before_regex' => $original_subject,
                                'after_regex' => $subject,
                                'question_id' => $webwork_question->id,
                                'updated_at' => now(),
                                'created_at' => now()]);
                        $webwork_table_subject_mappings[] = $subject;
                    }

                }
            }
            DB::commit();
            dd(DB::table('webwork_regex_subject_mappings')->where('question_id', $webwork_question->id)->first());
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
