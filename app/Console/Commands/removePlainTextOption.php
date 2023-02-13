<?php

namespace App\Console\Commands;

use App\Assignment;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class removePlainTextOption extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:plainTextOption';

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
    public function handle(): int
    {
        try {
            DB::beginTransaction();
            $assignments = Assignment::where('default_open_ended_text_editor', 'plain')
                ->get();
            echo "Updating default_open_ended_text_editor" . count($assignments) . "\r\n";
            foreach ($assignments as $assignment) {
                DB::table('remove_plain_text_options')
                    ->insert(['table_name' => 'assignments',
                        'table_id' => $assignment->id,
                        'default_open_ended_text_editor' => $assignment->default_open_ended_text_editor]);
                $assignment->default_open_ended_text_editor = 'rich';
                $assignment->save();

            }

            $assignments = Assignment::where('default_open_ended_submission_type', 'text')
                ->get();
            echo "Updating default_open_ended_submission_type" . count($assignments) . "\r\n";
            foreach ($assignments as $assignment) {
                DB::table('remove_plain_text_options')
                    ->insert(['table_name' => 'assignments',
                        'table_id' => $assignment->id,
                        'default_open_ended_submission_type' => $assignment->default_open_ended_submission_type]);
                $assignment->default_open_ended_submission_type = 'rich text';
                $assignment->save();
            }

            $assignment_questions = DB::table('assignment_question')
                ->where('open_ended_text_editor', 'plain')
                ->get();
            echo "Updating " . count($assignment_questions) . "\r\n";
            foreach ($assignment_questions as $assignment_question) {
                DB::table('assignment_question')
                    ->where('id', $assignment_question->id)
                    ->update(['open_ended_text_editor' => 'rich', 'updated_at' => now()]);
                DB::table('remove_plain_text_options')
                    ->insert(['table_name' => 'assignment_question',
                        'table_id' => $assignment_question->id,
                        'open_ended_text_editor' => $assignment_question->open_ended_text_editor]);
            }
            echo "Done.";

            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
