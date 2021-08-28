<?php

namespace App\Console\Commands\H5P;

use App\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class h5pfix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:h5p';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes the messed up h5p questions';

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

            // DB::statement('CREATE TABLE questions_original LIKE questions');
            //  DB::statement('INSERT questions_original SELECT * FROM questions');

            $questions = Question::where('technology', 'h5p')->get();
            DB::beginTransaction();
            foreach ($questions as $question) {
                $question->technology_iframe= str_replace('https://studio.libretexts.org/h5p/https://studio.libretexts.org/', 'https://studio.libretexts.org/',$question->technology_iframe);
                $question->technology_iframe =  str_replace('/embed/embed','/embed', $question->technology_iframe);
               $question->save();
            }
            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        echo "success";
    }
}
