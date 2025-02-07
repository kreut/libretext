<?php

namespace App\Console\Commands;

use App\Exceptions\Handler;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Generator;


class sendFrameworkItemsByQuestionUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:frameworkItemsByQuestionUpdates';

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
        $response['type'] = 'error';


        try {
            $question_ids = DB::table('framework_item_question')
                ->where('updated_at', '>=', Carbon::now()->subDay())
                ->select('question_id')
                ->groupBy('question_id')
                ->get('question_id')
                ->pluck('question_id')
                ->toArray();

            $descriptors_levels = [];
            foreach ($question_ids as $question_id) {
                $descriptors = DB::table('framework_item_question')
                    ->join('framework_descriptors', 'framework_item_question.framework_item_id', '=', 'framework_descriptors.id')
                    ->where('framework_item_type', 'descriptor')
                    ->where('question_id', $question_id)
                    ->select('framework_descriptors.id', 'framework_descriptors.descriptor AS text')
                    ->get();
                $descriptors_by_id = [];
                foreach ($descriptors as $descriptor) {
                    $descriptors_by_id[] = ['id' => $descriptor->id,
                        'text' => $descriptor->text];
                }

                $framework_levels = DB::table('framework_item_question')
                    ->join('framework_levels', 'framework_item_question.framework_item_id', '=', 'framework_levels.id')
                    ->where('framework_item_type', 'level')
                    ->where('question_id', $question_id)
                    ->select('framework_levels.id', 'framework_levels.title AS text')
                    ->get();
                $framework_levels_by_id = [];
                foreach ($framework_levels as $framework_level) {
                    $framework_levels_by_id[] = ['id' => $framework_level->id,
                        'text' => $framework_level->text];
                }
                $descriptors_levels[] = ['question_id' => $question_id, 'descriptors_levels' => [
                    'descriptors' => $descriptors_by_id,
                    'levels' => $framework_levels_by_id
                ]];
                $key = new HmacKey(config('myconfig.analytics_token'));

                $signer = new HS256($key);
                $generator = new Generator($signer);
                $jwt = $generator->generate();
                $response = Http::withToken($jwt)
                    ->post('https://lad.libretexts.org/api/webhooks/adapt-framework-sync', $descriptors_levels);

                if ($response->successful()) {
                    return 0;
                } else {
                    throw new Exception ($response->body());
                }

            }
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
        }
        return 1;
    }
}
