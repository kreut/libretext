<?php

namespace App\Console\Commands\DuplicateFixes\DeleteExtras;

use App\Libretext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class checkDeletedQuestionsInLearningTrees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:deletedQuestionsInLearningTrees';

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
    public function handle(Libretext $libretext)
    {
        $deleted_question_page_ids = DB::table('deleted_questions')
            ->select('page_id', 'library')
            ->get();
        $libraries = $libretext->libraries();

        foreach ($deleted_question_page_ids as $value) {
            $like = '{"name":"page_id","value":"' . $value->page_id . '"},{"name":"library","value":"' . $value->library . '"}';
            $orLike = 'bogus or like';
            foreach ($libraries as $formatted_library => $library) {
                if ($library === $value->library) {
                    $orLike = "<span class='library'>$formatted_library</span> - <span class='page_id'>$value->page_id</span>";
                }
            }
           $in_learning_trees = DB::table('learning_trees')
               ->where('learning_tree', 'LIKE', "%$like%")
               ->orWhere('learning_tree', 'LIKE', "%$orLike%")
               ->select('*')
               ->first();
            if ($in_learning_trees) {
                echo "$in_learning_trees->id  $value->library $value->page_id\r\n";
            }
        }
        return 0;
    }
}
