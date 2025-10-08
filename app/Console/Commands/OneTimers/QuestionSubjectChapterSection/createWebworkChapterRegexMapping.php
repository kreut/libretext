<?php

namespace App\Console\Commands\OneTimers\QuestionSubjectChapterSection;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class createWebworkChapterRegexMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:WebworkChapterRegexMapping';

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
            $webwork_table_chapter_mappings = [];
            $lower_cases = [];
            foreach ($webwork_questions as $webwork_question) {
                $webwork_code = $webwork_question->webwork_code;
                preg_match('/## DBchapter\((.*)\)\s*$/m', $webwork_code, $chapter_match);
                $original_chapter = $chapter_match[1] ?? null;
                if ($original_chapter) {
                    $chapter = $original_chapter;
                    if (preg_match('/^\d+(\.\d+)?$/', $chapter)) {
                        continue;
                    }
                    if ($chapter === "'ZZZ-Inserted Text'") {
                        continue;
                    }
                    if ($chapter === "TODO") {
                        continue;
                    }
                    if (preg_match('/^\s*\d+\s*[-:]?\s*Rosen\b/i', $chapter)) {
                        continue;
                    }
                    if (preg_match('/^Chem\s+\S+:/i', $chapter)) {
                        continue;
                    }


                    $chapter = preg_replace("/^'|'$/", '', $chapter);
                    $chapter = preg_replace('/^\d+\s*:\s*/', '', $chapter);
                    $chapter = preg_replace('/^Chapter\s*\d+\s*:\s*/i', '', $chapter);
                    $chapter = trim($chapter);
                    if ($chapter === "Midterm") {
                        continue;
                    }
                    if ($chapter === "Pre-Lab Quiz") {
                        continue;
                    }
                    if ($chapter === '') {
                        continue;
                    }
                    if (preg_match('/^\[REFER/i', $chapter)) {
                        continue;
                    }
                    $search = strtolower($chapter);
                    if (in_array($search,array_values($lower_cases))){

                        $lowered = array_map('strtolower', $lower_cases);
                        $key = array_search($search, $lowered, true);

                        if ($key !== false) {
                            $chapter = $key;
                        } else {
                            // not found
                        }
                    }
                    $lower_cases[$chapter] = $search;
                    //if ($original_chapter !== $chapter) {
                   // if (!in_array($chapter, $webwork_table_chapter_mappings)) {
                        DB::table('webwork_regex_chapter_mappings')
                            ->insert(['before_regex' => $original_chapter,
                                'after_regex' => $chapter,
                                'question_id' => $webwork_question->id,
                                'updated_at' => now(),
                                'created_at' => now()]);
                        $webwork_table_chapter_mappings[] = $chapter;
                   // }
                    //}
                }

            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
