<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Question;

class H5P extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:h5p';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the database with the latest h5p questions';

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
    public function handle(Question $question)
    {
       $question->store('h5p');
    }
}
