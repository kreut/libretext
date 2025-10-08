<?php

namespace App\Console\Commands\OneTimers\QuestionSubjectChapterSection;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class addInfoFromWebworkRegexResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:InfoFromWebworkRegexResults';

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
            $subject_mappings = DB::table('webwork_regex_subject_mappings')->get();
            foreach ($subject_mappings as $subject_mapping) {
                $question_id = $subject_mapping->question_id;
                $question_subject_id = $subject_mapping->subject_id;
                $chapter_mapping = DB::table('webwork_regex_chapter_mappings')
                    ->where('question_id', $question_id)
                    ->first();
                $question_chapter_id = $chapter_mapping ? $chapter_mapping->chapter_id : null;
                $question_section_id = null;
                if ($question_chapter_id) {
                    $section_mapping = DB::table('webwork_regex_section_mappings')
                        ->where('question_id', $question_id)
                        ->first();
                    $question_section_id = $section_mapping ? $section_mapping->section_id : null;
                }
                if (!DB::table('question_subject_chapter_section')->where('question_id', $question_id)->exists()) {
                    DB::table('question_subject_chapter_section')->insert(['question_id' => $question_id,
                        'question_subject_id' => $question_subject_id,
                        'question_chapter_id' => $question_chapter_id,
                        'question_section_id' => $question_section_id]);
                }

            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 0;
    }
}
