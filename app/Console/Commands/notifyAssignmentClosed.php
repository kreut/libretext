<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class notifyAssignmentClosed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:assignmentClosed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tell grades when an assignment is closed and ready for grading';

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
       //get all assignments that have just closed
        //for those courses
    }
}
