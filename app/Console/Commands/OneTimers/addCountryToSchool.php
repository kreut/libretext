<?php

namespace App\Console\Commands\OneTimers;

use App\School;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class addCountryToSchool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:countryToSchool';

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
            DB::table('schools')->update(['country' => 'US']);

            $csv = fopen('/Users/franciscaparedes/Downloads/canada.csv', 'r');
            while (($item = fgetcsv($csv, 10000, ",")) !== FALSE) {
                $school = new School();
                $name = $item[0];
                $bad_university_names = ['de Saint-Boniface', 'de Moncton'];
                foreach ($bad_university_names as $bad_name) {
                    if (strpos($name, $bad_name) !== false) {
                        $name = "Université $bad_name";
                    }
                }
                if (strpos($name, 'rieure') !== false
                    && strpos($name, "cole de technologie sup") !== false) {
                    $name = "École de technologie supérieure";
                }
                if (strpos($name, "cole nationale d'administration publique") !== false) {
                    $name = "École nationale d'administration publique";
                }

                if (strpos($name, "cole Polytechnique de Montr") !== false) {
                    $name = "École Polytechnique de Montréal";
                }

                if (strpos($name, "HEC Montr") !== false) {
                    $name = "HEC Montréal";
                }



                    $school->name = $name;
                    $school->country = 'CA';
                    echo "$name\r\n";
                    $school->save();
                }

                DB::commit();
            }
        catch
            (Exception $e) {
                DB::rollback();
                echo $e->getMessage();

            }
        return 0;
    }
}
