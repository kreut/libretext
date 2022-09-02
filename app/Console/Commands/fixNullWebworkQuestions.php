<?php

namespace App\Console\Commands;

use App\Exceptions\Handler;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixNullWebworkQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:nullWebworkQuestions';

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
        try {
            $bad_webworks = Question::where('technology', 'webwork')
                ->whereNull('technology_id')
                ->whereNull('webwork_code')
                ->select('id', 'technology_iframe')
                ->get();
            DB::beginTransaction();
            foreach ($bad_webworks as $bad_webwork) {
                $regex = '/(sourceFilePath=)(.*)(.pg)/';
                preg_match($regex, $bad_webwork->technology_iframe, $matches);
               $technology_id= str_replace('sourceFilePath=', '', $matches[0]) . "\r\n";
                if (!DB::table('bad_webworks')->where('question_id', $bad_webwork->id)->first()) {
                    DB::table('bad_webworks')->insert(['question_id' => $bad_webwork->id, 'technology_id' => $technology_id]);
                }
            }
            DB::commit();
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);
        }
        return 0;
    }
}
