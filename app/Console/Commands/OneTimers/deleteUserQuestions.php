<?php

namespace App\Console\Commands\OneTimers;

use App\Question;
use App\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Laravel\Facades\Telegram;

class deleteUserQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:userQuestions';

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
            $user_id = 2589;
            $user = User::find(2589);
            echo $user->first_name . ' ' . $user->last_name . "\r\n";
            DB::beginTransaction();
            $question_ids = Question::where('question_editor_user_id', $user_id)->select('id')->pluck('id')->toArray();
            Telegram::sendMessage([
                'chat_id' => config('myconfig.telegram_channel_id'),
                'parse_mode' => 'HTML',
                'text' => "Jennifer Black " . implode(',', $question_ids)
            ]);
            $num_question_tags = DB::table('question_tag')->whereIn('question_id', $question_ids)->count();
            echo "$num_question_tags question tags deleted";
            DB::table('question_tag')->whereIn('question_id', $question_ids)->delete();
            $num_questions= Question::count();
            echo "$num_questions questions\r\n";
            Question::where('question_editor_user_id', $user_id)->delete();
            $num_questions= Question::count();
            echo "$num_questions questions\r\n";
            echo "Done!";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
