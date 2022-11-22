<?php

namespace App\Console\Commands\OneTimers;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class removeSpanTagFromH5p extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:spanTagFromH5P';

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
            DB::beginTransaction();
            $questions = Question::where('non_technology_html', 'LIKE', '%<span lang="es">%')
                ->get();
            $num_questions = count($questions);
            foreach ($questions as $question) {
                echo $question->id . "\r\n";
                echo $question->non_technology_html . "\r\n";
                $question->non_technology_html = '';
                $question->non_technology = 0;
                $question->save();
            }
            DB::commit();
            echo "$num_questions fixed.";

        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();

        }
        return 0;
    }
}
