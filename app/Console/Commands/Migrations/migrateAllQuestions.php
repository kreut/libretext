<?php

namespace App\Console\Commands\Migrations;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use function now;

class migrateAllQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:allQuestions';

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
            echo "Start\r\n";
            $start = microtime(true);
            $questions = Question::where('library', '<>', 'adapt')->limit(25000)->get();
            DB::beginTransaction();
            $migrated_question_ids = DB::table('adapt_mass_migrations')
                ->get('new_page_id')
                ->pluck('new_page_id')
                ->toArray();
            $default_non_instructor_editor = DB::table('users')
                ->where('email', 'Default Non-Instructor Editor has no email')
                ->first();
            $saved_questions_folder = DB::table('saved_questions_folders')
                ->where('user_id', $default_non_instructor_editor->id)
                ->where('type', 'my_questions')
                ->first();
            $num_questions = count($questions);
            foreach ($questions as $question) {
                if (!in_array($question->id, $migrated_question_ids)) {
                    $new_page_id = $question->id;
                    DB::table('adapt_mass_migrations')->insert([
                        'original_library' => $question->library,
                        'original_page_id' => $question->page_id,
                        'new_page_id' => $new_page_id,
                        'migrated' => 1,
                        'created_at' => now(),
                        'updated_at' => now()]);
                    $question->question_editor_user_id = $default_non_instructor_editor->id;
                    $question->folder_id = $saved_questions_folder->id;
                    $question->library = 'adapt';
                    $question->page_id = $question->id;
                    $question->save();
                    $migrated_question_ids[] = $question->id;
                }
            }
            $seconds = microtime(true) - $start;
            echo "Migrated $num_questions questions. Finished in $seconds seconds.";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();

        }

        return 0;
    }
}
