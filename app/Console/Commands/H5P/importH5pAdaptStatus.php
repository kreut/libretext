<?php

namespace App\Console\Commands\H5P;

use App\H5pAdaptStatus;
use App\H5pAdaptType;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class importH5PAdaptStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:h5pAdaptStatuss';

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
    public function handle(H5pAdaptStatus $h5pAdaptStatus)
    {

        $csv = fopen('/Users/franciscaparedes/Downloads/h5p_adapt_status (1).csv', 'r');
        DB::beginTransaction();
        try {
            while (($item = fgetcsv($csv, 10000)) !== FALSE) {
                $name = trim($item[0]);
                $category = $item[1];
                $adapt_status = $item[2];
                $xapi = $item[3];
                echo $name;
                $h5pAdaptStatus = new H5pAdaptStatus();
                if (!$h5pAdaptStatus->where('name', $name)->first()) {
                    $h5pAdaptStatus->name = $name;
                    $h5pAdaptStatus->category = $category;
                    $h5pAdaptStatus->adapt_status = $adapt_status;
                    $h5pAdaptStatus->xapi = $xapi;
                    $h5pAdaptStatus->save();
                }
            }
            DB::commit();
            echo "Imported";
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getmessage();
            return 1;
        }

        return 0;
    }
}
