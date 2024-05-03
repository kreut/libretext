<?php

namespace App\Console\Commands\H5P;

use App\H5pCollection;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class getAdaptQuestiontIdsForH5p extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:AdaptQuestionIdsForH5p';

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
     * @param H5pCollection $h5pCollection
     * @return int
     */
    public function handle(H5pCollection $h5pCollection): int
    {

        $csv = fopen('/Users/franciscaparedes/Downloads/17b38314-abd8-4b83-ba57-36651eb6752a.csv', 'r');
        $completed_h5p_ids = DB::table('h5p_id_adapt_question_id')->limit(100)->get()->pluck('h5p_id')->toArray();
        try {
            while (($item = fgetcsv($csv, 10000)) !== FALSE) {
                $h5p_id = trim($item[0]);
                $email = trim($item[1]);
                if ($h5p_id && !in_array($h5p_id, $completed_h5p_ids)) {
                    $start_time = microtime(true);
                    $response = $h5pCollection->getAdaptIdByH5pId($h5p_id, $email);
                    $adapt_question_id = $response['adapt_question_id'] ?? null;
                    $total_time = microtime(true)-$start_time;
                    echo $total_time;
                    DB::table('h5p_id_adapt_question_id')->insert([
                        'h5p_id' => $h5p_id,
                        'email' => $email,
                        'adapt_question_id' => $adapt_question_id,
                        'status' => $response['type'] === 'success' ? 'success' : $response['message'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    echo "$h5p_id $email $total_time\r\n";
                    $completed_h5p_ids[]= $h5p_id;
                }

            }
            echo "Imported";
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getmessage();
            return 1;
        }

        return 0;
    }
}
