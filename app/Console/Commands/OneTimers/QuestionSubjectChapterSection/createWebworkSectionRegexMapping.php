<?php

namespace App\Console\Commands\OneTimers\QuestionSubjectChapterSection;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class createWebworkSectionRegexMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:WebworkSectionRegexMapping';

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
            $webwork_table_section_mappings = [];
            $lower_cases = [];
            foreach ($webwork_questions as $webwork_question) {
                $webwork_code = $webwork_question->webwork_code;
                preg_match('/## DBsection\((.*)\)\s*$/m', $webwork_code, $section_match);
                $original_section = $section_match[1] ?? null;
                if ($original_section) {
                    $section = trim($original_section);
                    $section = preg_replace("/^[\"'‘’]+|[\"'‘’]+$/u", '', $section);          // remove surrounding quotes
                    $section = preg_replace('/^(?:\d+\.\d+\s*)+/', '', $section); // remove leading 10.3 or 5.2 patterns
                    $section = preg_replace('/^\d+\s*:\s*/', '', $section);     // remove "12: " prefixes
                    $section = preg_replace('/^\s*:\s*/', '', $section);
// Now skip checks
                    if (preg_match('/^\d+(\.\d+)?$/', $section)) {
                        continue;
                    }  // just a number
                    if ($section === "TODO") {
                        continue;
                    }
                    if ($section === "???") {
                        continue;
                    }
                    if ($section === "Midterm") {
                        continue;
                    }
                    if ($section === "Pre-Lab Quiz") {
                        continue;
                    }
                    if ($section === "ZZZ-Inserted Text") {
                        continue;
                    }
                    if (preg_match('/^\s*\d+\s*[-:]?\s*Rosen\b/i', $section)) {
                        continue;
                    }
                    if (preg_match('/^\[REFER/i', $chapter)) {
                        continue;
                    }
                    if ($section === '') {
                        continue;
                    }

                    $search = strtolower($section);
                    if (in_array($search,array_values($lower_cases))){

                        $lowered = array_map('strtolower', $lower_cases);
                        $key = array_search($search, $lowered, true);

                        if ($key !== false) {
                            $section= $key;
                        } else {
                            // not found
                        }
                    }
                    $lower_cases[$section] = $search;
                    // if ($original_section !== $section) {
                    //if (!in_array($section, $webwork_table_section_mappings)) {
                        DB::table('webwork_regex_section_mappings')
                            ->insert(['before_regex' => $original_section,
                                'after_regex' => $section,
                                'question_id' => $webwork_question->id,
                                'updated_at' => now(),
                                'created_at' => now()]);
                        $webwork_table_section_mappings[] = $section;
                        // }
                   // }
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
