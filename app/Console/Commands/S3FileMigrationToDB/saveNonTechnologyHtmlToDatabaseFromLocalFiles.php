<?php

namespace App\Console\Commands\S3FileMigrationToDB;

use App\Question;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class saveNonTechnologyHtmlToDatabaseFromLocalFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'save:nonTechnologyHtmlToDatabaseFromLocalFiles';

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
                ->select('id', 'library', 'page_id')
                ->get();
            $num = count($non_technologies);
            foreach ($non_technologies as $key => $non_technology) {
                echo $num - $key . "\r\n";
                $path = "production-libraries/$non_technology->library/$non_technology->page_id.php";
                if (Storage::disk('local')->exists($path)) {
                    $non_technology_html = Storage::disk('local')->get($path);
                    DB::table('non_technology_htmls')->insert([
                        'question_id' => $non_technology->id,
                        'non_technology_html' => $non_technology_html]);
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;

        }
        return 0;
    }
}
