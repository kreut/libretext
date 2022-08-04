<?php

namespace App\Console\Commands;

use App\Exceptions\Handler;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class removeOldCurrentQuestionEditors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:oldCurrentQuestionEditors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'If a non-instructor editor leaves the page by closing the browser, we need a way to remove the fact that they are there';

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
     * @throws Exception
     */
    public function handle(): int
    {
        $last_5_minutes = Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s');
        try {
            DB::table('current_question_editors')
                ->where('updated_at', '<=', $last_5_minutes)
                ->delete();
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        return 0;
    }
}
