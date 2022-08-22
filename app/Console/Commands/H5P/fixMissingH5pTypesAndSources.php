<?php

namespace App\Console\Commands\H5P;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixMissingH5pTypesAndSources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:missingH5pTypesAndSources';

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
        $h5p_questions = Question::where('technology', 'h5p')
            ->select('id', 'technology_id')
            ->whereNull('h5p_type')
            ->get();
        $num_questions = count($h5p_questions);
        DB::beginTransaction();
        try {
            foreach ($h5p_questions as $key => $question) {
                $h5p_object = Helper::h5pApi($question->technology_id);
                $h5p_type = $h5p_object[0]['type'] ?? null;
                $source_url = !isset($h5p_object[0]['h5p_source']) || !$h5p_object[0]['h5p_source'] ? "https://studio.libretexts.org/h5p/$question->technology_id" : $h5p_object[0]['h5p_source'];
                $question->h5p_type = $h5p_type;
                $question->source_url = $source_url;
                echo $num_questions - $key . " $question->id  $h5p_type  $source_url\r\n";
                $question->save();
            }
            DB::commit();
            return 0;
        } catch (Exception $e) {
            DB::rollback();
            echo $num_questions - $key . " $question->id " . $e->getMessage() . "\r\n";
            return 1;

        }

    }
}
