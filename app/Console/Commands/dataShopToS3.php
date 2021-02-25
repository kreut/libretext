<?php

namespace App\Console\Commands;

use App\DataShop;
use App\Exceptions\Handler;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class dataShopToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dataShop:toS3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends the datashop table to s3';

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
     * @throws Exception
     */
    public function handle()
    {

        try {
            $table = DataShop::all();
            $file_path = Storage::disk('local')->path('') . 'datashop/' . Carbon::now()->format('Y-m-d_g_i_s_a') . ".csv";
            $file = fopen($file_path, 'w');
            foreach ($table as $row) {
                fputcsv($file, $row->toArray());
            }
            fclose($file);
            Storage::disk('s3')->put("datashop/" .basename($file_path), file_get_contents($file_path));
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
