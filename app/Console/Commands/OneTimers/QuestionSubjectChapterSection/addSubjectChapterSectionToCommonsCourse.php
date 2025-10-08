<?php

namespace App\Console\Commands\OneTimers\QuestionSubjectChapterSection;

use App\Console\Commands\OneTimers\Excpetion;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class addSubjectChapterSectionToCommonsCourse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:SubjectChapterSectionToCommonsCourse';

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
            $num_updated = 0;
            $num_inserted = 0;
            $completed_question_ids = DB::table('question_subject_chapter_section')
                ->select('question_id')
                ->get()
                ->pluck('question_id')
                ->toArray();

            $commons_courses = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->where('users.id', 1377)
                ->select('courses.id')
                ->get()
                ->pluck('id')
                ->toArray();
            $commons_course_question_ids = DB::table('assignment_question')
                ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
                ->whereIn('assignments.course_id', $commons_courses)
                ->select('question_id')
                ->get()
                ->pluck('question_id')
                ->toArray();
            $commons_course_question_ids = array_unique($commons_course_question_ids);
            foreach ($commons_course_question_ids as $question_id) {
                if (in_array($question_id, $completed_question_ids)){
                    continue;
                }
                $commons_course = DB::table('assignment_question')
                    ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
                    ->join('courses', 'assignments.course_id', '=', 'courses.id')
                    ->where('assignment_question.question_id', $question_id)
                    ->whereIn('assignments.course_id', $commons_courses)
                    ->select('assignments.id AS assignment_id', 'assignments.name AS chapter', 'courses.name AS subject', 'courses.id AS commons_course_id')
                    ->first();
                $commons_course_mapping = DB::table('commons_course_name_mappings')
                    ->where('course_id', $commons_course->commons_course_id)
                    ->first();
                $subject = $commons_course_mapping ? $commons_course_mapping->mapping : $commons_course->subject;
                $commons_course_regex_chapter_mapping = DB::table('commons_course_regex_chapter_mappings')
                    ->where('assignment_id', $commons_course->assignment_id)
                    ->first();

                $chapter = $commons_course_regex_chapter_mapping ? $commons_course_regex_chapter_mapping->after_regex
                    : $commons_course->chapter;

                $chapter = trim($chapter);
                $subject = trim($subject);

                $question_subject_id = $this->_getQuestionLevelId('question_subjects', $subject);
                $question_chapter_id = $this->_getQuestionLevelId('question_chapters', $chapter, $question_subject_id);
                $exists = DB::table('question_subject_chapter_section')
                    ->where('question_id', $question_id)
                    ->exists();

                DB::table('question_subject_chapter_section')->updateOrInsert(
                    ['question_id' => $question_id],
                    [
                        'question_subject_id' => $question_subject_id,
                        'question_chapter_id' => $question_chapter_id,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                if ($exists) {
                    $num_updated++;
                    echo "Updated: $num_updated\r\n";
                } else {
                    $num_inserted++;
                    echo "Inserted: $num_inserted\r\n";
                }
                echo "$subject $chapter\r\n";
            }

            echo "Number updated: $num_updated\r\n";
            echo "Number inserted: $num_inserted\r\n";

        } catch (Exception $e) {
            echo $e->getMessage();

        }
        return 0;
    }

    private function _getQuestionLevelId($table, $name, $parent_level_id = '')
    {
        $name = trim($name);
        $name = trim($name, "'");
        switch ($table) {
            case('question_subjects'):
                $item = DB::table('question_subjects')
                    ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                    ->first();
                if ($item) {
                    return $item->id;
                }
                return DB::table('question_subjects')
                    ->insertGetId(['name' => $name,
                        'created_at' => now(),
                        'updated_at' => now()]);
            case('question_chapters'):
                $item = DB::table('question_chapters')
                    ->where('name', $name)
                    ->where('question_subject_id', $parent_level_id)
                    ->first();
                if ($item) {
                    return $item->id;
                }
                return DB::table('question_chapters')
                    ->insertGetId(['name' => $name,
                        'question_subject_id' => $parent_level_id,
                        'created_at' => now(),
                        'updated_at' => now()]);
            case('question_sections'):
                $item = DB::table('question_sections')
                    ->where('name', $name)
                    ->where('question_chapter_id', $parent_level_id)
                    ->first();
                if ($item) {
                    return $item->id;
                }
                return DB::table('question_sections')
                    ->insertGetId(['name' => $name,
                        'question_chapter_id' => $parent_level_id,
                        'created_at' => now(),
                        'updated_at' => now()]);
            default:
                throw new Excpetion ("Incorrect table");

        }
    }

}
