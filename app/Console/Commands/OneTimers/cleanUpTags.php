<?php

namespace App\Console\Commands\OneTimers;

use App\Tag;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class cleanUpTags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:Tags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds spaces to some of the tags';

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

            $imported_tags = DB::table('imported_tags')->get();
            DB::beginTransaction();
            foreach ($imported_tags as $imported_tag) {
                $tag = Tag::where('tag', $imported_tag->original_tag)->first();
                if ($tag) {
                    DB::table('tags')->where('id', $tag->id)
                        ->update(['tag' => $imported_tag->cleaned_up_tag,
                            'updated_at' => now()]);
                }
            }
            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
