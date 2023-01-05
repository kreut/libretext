<?php

namespace App\Console\Commands\OneTimers;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class removeTags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:tags {tag}';

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
            $tag = DB::table('tags')->where('tag', $this->argument('tag'))->first();
            if (!$tag) {
                echo "$tag does not exist.";
                return 1;
            }
            DB::beginTransaction();
            $num = DB::table('question_tag')->where('tag_id', $tag->id)->count();
            DB::table('question_tag')->where('tag_id', $tag->id)->delete();
            DB::table('tags')->where('id', $tag->id)->delete();
            DB::commit();
            echo "$num question tags removed.";
        } catch (Exception $e) {
            echo $e->getMessage();
            return 1;

        }
        return 0;
    }
}
