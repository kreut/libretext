<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class addTextbookUrlToDataShops extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:textbookUrlToDataShopIds';

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
            $textbook_urls = DB::table('data_shops')
                ->join('courses', 'data_shops.class', '=', 'courses.id')
                ->whereNotNull('courses.textbook_url')
                ->select('courses.name', 'class', 'courses.textbook_url')
                ->groupBy('class')
                ->get();
            foreach ($textbook_urls as $value) {
                echo $value->class . " " . $value->name  ." " . $value->textbook_url . "\r\n";
                DB::table('data_shops')
                    ->where('class', $value->class)
                    ->update(['textbook_url' => $value->textbook_url]);
            }
            DB::commit();
            return 0;
        } catch (Exception $e) {
            DB::rollBack();
            echo $e->getMessage();
        }
        return 1;

    }
}
