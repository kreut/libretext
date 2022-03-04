<?php

namespace App\Console\Commands\SearchImprovementFixes\AuthorFixes;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class deleteAuthorTags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:authorTags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes the author tags from the database';

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
            $tags = DB::table('tags')
                ->where('tag', 'like', 'author%')
                ->select('id', 'tag')
                ->get();
            foreach ($tags as $tag) {
                echo $tag->tag . "\r\n";
                DB::table('question_tag')->where('tag_id', $tag->id)->delete();
                DB::table('tags')->where('id', $tag->id)->delete();
            }
            DB::commit();
            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();
            return 1;
        }

    }
}
