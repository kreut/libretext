<?php

namespace App\Console\Commands\H5P;

use App\Exceptions\Handler;
use App\H5pTypeAndSourceUrl;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class getH5pTypeAndSourceUrl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:H5pTypeAndSourceUrl {question_id}';

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
     * @throws Exception
     */
    public function handle()
    {
        $question_id = $this->argument('question_id');
        $h5p_questions = DB::table('questions')
            ->where('technology', 'h5p')
            ->select('id', 'technology_id')
            ->orderBy('id')
            ->where('id', '>', $question_id)
            ->get();
        $num_questions = count($h5p_questions);
        foreach ($h5p_questions as $key => $question) {
            if (DB::table('h5p_type_and_source_urls')->where('question_id', $question->id)->first()) {
                echo "$question->id already has an h5p type in the table.\r\n";
                continue;
            }
            try {
                $endpoint = "https://studio.libretexts.org/api/h5p/$question->technology_id";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $endpoint);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                curl_setopt($ch, CURLOPT_FAILONERROR, true); // Required for HTTP error codes to be reported via our call to curl_error($ch)

                $output = curl_exec($ch);
                $error_msg = curl_errno($ch) ? curl_error($ch) : '';
                if ($error_msg) {
                    throw new Exception ("Import All H5P CRON job did not work: $error_msg");
                }
                $h5p_object = json_decode($output, 1);

                curl_close($ch);

                $type = !isset($h5p_object[0]['type']) ? 'None provided' : $h5p_object[0]['type'];
                $source_url = !isset($h5p_object[0]['h5p_source']) || !$h5p_object[0]['h5p_source'] ? "https://studio.libretexts.org/h5p/$question->technology_id" : $h5p_object[0]['h5p_source'];
                $h5pTypeAndSourceUrl = new H5pTypeAndSourceUrl();
                $h5pTypeAndSourceUrl->question_id = $question->id;
                $h5pTypeAndSourceUrl->h5p_type = $type;
                $h5pTypeAndSourceUrl->source_url = $source_url;
                $h5pTypeAndSourceUrl->save();
                echo $num_questions - $key . " $question->id\r\n";
            } catch (Exception $e) {
                echo $num_questions - $key . " " . $e->getMessage() . "\r\n";
                $h = new Handler(app());
                $h->report($e);
            }
        }
        return 0;
    }
}
