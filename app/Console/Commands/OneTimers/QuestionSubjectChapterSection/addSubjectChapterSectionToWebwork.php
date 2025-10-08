<?php

namespace App\Console\Commands\OneTimers\QuestionSubjectChapterSection;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class addSubjectChapterSectionToWebwork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:SubjectChapterSectionToWebwork';

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
            $webwork_regex_subject_mappings = DB::table('webwork_regex_subject_mappings')
                ->get();
            $question_subjects = DB::table('question_subjects')->get();
            $subjects = [];
            foreach ($question_subjects as $question_subject) {
                $subjects[$question_subject->name] = $question_subject->id;
            }
            foreach ($webwork_regex_subject_mappings as $webwork_regex_subject_mapping) {
                $question_id = $webwork_regex_subject_mapping->question_id;
                if (!$webwork_regex_subject_mapping->subject_id) {
                    if (in_array($webwork_regex_subject_mapping->after_regex, array_keys($subjects))) {
                        $subject_id = $subjects[$webwork_regex_subject_mapping->after_regex];
                    } else {
                        $subject_id = DB::table('question_subjects')->insertGetId([
                            'name' => $webwork_regex_subject_mapping->after_regex,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $subjects[$webwork_regex_subject_mapping->after_regex] = $subject_id;
                    }
                    DB::table('webwork_regex_subject_mappings')
                        ->where('question_id', $question_id)
                        ->update(['subject_id' => $subject_id]);
                } else {
                    $subject_id = $webwork_regex_subject_mapping->subject_id;
                }

                $webwork_regex_chapter_mapping = DB::table('webwork_regex_chapter_mappings')
                    ->where('question_id', $question_id)
                    ->first();

                if ($webwork_regex_chapter_mapping) {
                    $question_chapter = DB::table('question_chapters')
                        ->where('name', $webwork_regex_chapter_mapping->after_regex)
                        ->where('question_subject_id', $subject_id)
                        ->first();
                    if ($question_chapter) {
                        $chapter_id = $question_chapter->id;
                    } else {
                        $chapter_id = DB::table('question_chapters')->insertGetId([
                            'name' => $webwork_regex_chapter_mapping->after_regex,
                            'question_subject_id' => $subject_id,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                    DB::table('webwork_regex_chapter_mappings')
                        ->where('question_id', $question_id)
                        ->update(['chapter_id' => $chapter_id]);

                    $webwork_regex_section_mapping = DB::table('webwork_regex_section_mappings')
                        ->where('question_id', $question_id)
                        ->first();

                    if ($webwork_regex_section_mapping) {
                        $question_section = DB::table('question_sections')
                            ->where('name', $webwork_regex_section_mapping->after_regex)
                            ->where('question_chapter_id', $chapter_id)
                            ->first();
                        if ($question_section) {
                            $section_id = $question_section->id;
                        } else {
                            $section_id = DB::table('question_sections')->insertGetId([
                                'name' => $webwork_regex_section_mapping->after_regex,
                                'question_chapter_id' => $chapter_id,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                        DB::table('webwork_regex_section_mappings')
                            ->where('question_id', $question_id)
                            ->update(['section_id' => $section_id]);
                    }
                }
            }
            exit;

            $completed_question_ids = DB::table('question_subject_chapter_section')
                ->select('question_id')
                ->get()
                ->pluck('question_id')
                ->toArray();
            $webwork_questions = Question::where('technology', 'webwork')
                ->whereNotIn('id', $completed_question_ids)
                ->whereNotNull('webwork_code')
                ->get();
            foreach ($webwork_questions as $webwork_question) {
                $webwork_code = $webwork_question->webwork_code;
                preg_match('/## DBsubject\((.*)\)\s*$/m', $webwork_code, $subject_match);
                preg_match('/## DBchapter\((.*)\)\s*$/m', $webwork_code, $chapter_match);
                preg_match('/## DBsection\((.*)\)\s*$/m', $webwork_code, $section_match);

                $subject = $subject_match[1] ?? null;
                $chapter = $chapter_match[1] ?? null;
                $section = $section_match[1] ?? null;
                echo "$webwork_question->id: $subject, $chapter, $section\r\n";
                $question_subject_id = $this->_getQuestionLevelId('question_subjects', $subject);
                $question_chapter_id = $this->_getQuestionLevelId('question_chapters', $chapter, $question_subject_id);
                $question_section_id = $this->_getQuestionLevelId('question_sections', $section, $question_chapter_id);
                DB::table('question_subject_chapter_section')
                    ->insert(['question_id' => $webwork_question->id,
                        'question_subject_id' => $question_subject_id,
                        'question_chapter_id' => $question_chapter_id,
                        'question_section_id' => $question_section_id,
                        'created_at' => now(),
                        'updated_at' => now()]);
            }
            $empty_question_subject_id = DB::table('question_subjects')->where('name', '')->first()->id;
            $empty_question_chapter_id = DB::table('question_chapters')->where('name', '')->first()->id;
            $empty_question_section_id = DB::table('question_sections')->where('name', '')->first()->id;
            $no_informations = DB::table('question_subject_chapter_section')
                ->where('question_subject_id', $empty_question_subject_id)
                ->orWhere('question_chapter_id', $empty_question_chapter_id)
                ->orWhere('question_section_id', $empty_question_section_id)
                ->select('question_id')
                ->get()
                ->pluck('question_id')
                ->toArray();
            echo "No informations: " . count($no_informations) . "\r\n";
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
                $question = Question::find($question_id);
                if (in_array($question_id, $no_informations) || !$question->question_subject_id) {
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
                    // 8️⃣ Skip if the remaining string is too short (<4 letters)
                    if (strlen(preg_replace('/[^A-Za-z]/', '', $chapter)) <= 4) {
                        continue;
                    }
                    //echo "$commons_course->subject: $chapter \r\n";
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
            }

            echo "Number updated: $num_updated\r\n";
            echo "Number inserted: $num_inserted\r\n";

        } catch (Exception $e) {
            echo $e->getMessage();

        }
        return 0;
    }

    /**
     * @throws Exception
     */
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
                throw new Exception ("Incorrect table");

        }
    }

}
