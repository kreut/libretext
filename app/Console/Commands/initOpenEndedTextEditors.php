<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class initOpenEndedTextEditors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:OpenEndedTextEditor';

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
     * @return mixed
     */
    public function handle()
    {
        DB::beginTransaction();
        DB::table('assignment_question')->where('open_ended_submission_type' , 'text')
           ->update(['open_ended_text_editor' => 'rich']);
        DB::table('assignments')->where('default_open_ended_submission_type' , 'text')
            ->update(['default_open_ended_text_editor' => 'rich']);
        DB::commit();
    }
}
