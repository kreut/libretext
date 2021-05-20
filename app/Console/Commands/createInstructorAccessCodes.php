<?php

namespace App\Console\Commands;

use App\InstructorAccessCode;
use Illuminate\Console\Command;

class createInstructorAccessCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:instructorAccessCodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create student access codes.';

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
        $access_code = substr(sha1(mt_rand()), 17, 12);
        for ($i = 0; $i < 450; $i++) {
            $instructorAccessCode = new InstructorAccessCode();
            $instructorAccessCode->access_code = substr(sha1(mt_rand()), 17, 12);
            $instructorAccessCode->save();
        }
    }
}
