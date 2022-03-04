<?php

namespace App\Console\Commands\SearchImprovementFixes\AuthorFixes;

use App\QuestionAuthor;
use App\Tag;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixAuthors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:Authors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Converts the imathas and webwork author tags into actual authors';

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

        $technologies = ['imathas', 'webwork'];
        try {
            foreach ($technologies as $technology) {
                $author_tags = DB::table('tags')
                    ->join('question_tag', 'tags.id', '=', 'question_tag.tag_id')
                    ->join('questions', 'question_tag.question_id', '=', 'questions.id')
                    ->where('tag', 'like', 'author%')
                    ->where('technology', $technology)
                    ->where('author', null)
                    ->select('question_id', 'tag', 'tags.id AS tag_id')
                    ->get();
                $question_authors = DB::table('question_authors')
                    ->select('id')
                    ->get()
                    ->pluck('id')
                    ->toArray();
                $count = count($author_tags);
                foreach ($author_tags as $key => $author_tag) {
                    switch ($technology) {
                        case('imathas'):
                            $clean_author = ucwords(str_replace('author-', '', $author_tag->tag));
                            $last_first_array = explode('Mb', $clean_author);
                            $clean_author_array = [];
                            foreach ($last_first_array as $last_first) {
                                $last_first = explode(',', rtrim($last_first, ','));
                                $clean_author_array[] = isset($last_first[1]) ? trim($last_first[1]) . ' ' . trim($last_first[0]) : trim($last_first[0]);
                            }
                            $clean_author = ucwords(implode(', ', $clean_author_array));
                            break;
                        case('webwork'):
                            $clean_author = ucwords(str_replace(["', '", "','", 'author-', ' and '], [", ", ', ', '', ', '], $author_tag->tag));
                            break;
                        default:
                            echo "Should be webwork or imathas";
                            exit;
                    }

                    if (!in_array($author_tag->question_id, $question_authors)) {
                        echo ($count - $key) . ' ' . $author_tag->question_id . ' ' . $clean_author . "\r\n";
                        $questionAuthor = new QuestionAuthor();
                        $questionAuthor->id = $author_tag->question_id;
                        $questionAuthor->author = $clean_author;
                        $questionAuthor->save();
                        $question_authors[] = $author_tag->question_id;
                    }

                    DB::table('question_tag')
                        ->where('tag_id', $author_tag->tag_id)
                        ->where('question_id', $author_tag->question_id)
                        ->delete();
                    if (!DB::table('question_tag')
                        ->where('tag_id', $author_tag->tag_id)
                        ->first()) {
                        Tag::where('id', $author_tag->tag_id)->delete();
                        echo 'delete';
                    }
                }
            }
            return 0;
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }
    }
}
