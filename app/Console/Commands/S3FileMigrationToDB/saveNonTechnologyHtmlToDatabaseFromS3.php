<?php

namespace App\Console\Commands\S3FileMigrationToDB;

use App\Exceptions\Handler;
use App\Question;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class saveNonTechnologyHtmlToDatabaseFromS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'save:nonTechnologyHtmlToDatabaseFromS3';

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

        try {
            DB::beginTransaction();
            $non_technologies = Question::where('non_technology', 1)
                ->where('updated_at', '>', Carbon::now()->subHours(12)->toDateTimeString())
                ->select('id', 'library', 'page_id')
                ->get();
            $num = count($non_technologies);
            foreach ($non_technologies as $key => $non_technology) {
                echo $num - $key . "\r\n";
                $path = "$non_technology->library/$non_technology->page_id.php";
                if (Storage::disk('s3')->exists($path)) {
                    $non_technology->non_technology_html = Storage::disk('s3')->get($path);
                    $non_technology->save();
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            return 1;

        }
        return 0;
    }
}
