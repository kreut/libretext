<?php

namespace App\Console\Commands;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class convertH5pToLibreStudio extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'convert:h5pToLibreStudio';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rewrites the h5p as libre studio';

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
                preg_match('/src="([^"]*)"/i', $question->technology_iframe, $output_array);
                $id = str_replace('https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&amp;id=', '', $output_array[1]);
                $question->technology_iframe = str_replace($output_array[1], "https://studio.libretexts.org/h5p/$id/embed", $question->technology_iframe);
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
