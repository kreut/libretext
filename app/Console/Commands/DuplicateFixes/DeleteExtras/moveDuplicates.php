<?php

namespace App\Console\Commands\DuplicateFixes\DeleteExtras;

use App\Question;
use App\Seed;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class moveDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'move:duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'You will have to create some new tables first:';
    /*DB::statement('CREATE TABLE deleted_questions LIKE questions; ');
    DB::statement('CREATE TABLE deleted_question_tag LIKE question_tag; ');
    DB::statement('CREATE TABLE deleted_seeds LIKE seeds; ');*/


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
        echo Carbon::now() . "\r\n";
        try {
            $technologies = ['h5p', 'webwork', 'imathas'];
            $assignment_question_ids = DB::table('assignment_question')
                ->select('question_id')
                ->get()
                ->pluck('question_id')
                ->toArray();
            foreach ($technologies as $technology) {
                $questions = DB::table('questions')
                    ->where('technology', $technology)
                    ->groupBy('technology_id')
                    ->select('technology_id', DB::raw('COUNT(*) AS count'))
                    ->having('count', '>', 1)
                    ->get();

                $technology_ids = [];
                foreach ($questions as $question) {
                    $technology_ids[] = $question->technology_id;
                }

                DB::beginTransaction();
                foreach ($technology_ids as $technology_id) {
                    $questions = DB::table('questions')
                        ->where('technology_id', $technology_id)
                        ->where('technology', $technology)
                        ->select('id')
                        ->get();
                    // echo count($questions) . " $technology $technology_id\r\n";
                    $in_assignment = false;
                    foreach ($questions as $key => $question) {
                        if (in_array($question->id, $assignment_question_ids)) {
                            $questions->forget($key);//keep just one of them
                            $in_assignment = true;
                        }
                    }
                    $questions = $questions->values();
                    if ($in_assignment) {
                        if (count($questions) > 1) {
                            $questions->forget(0);//keep just one of them
                        }
                    }
                    foreach ($questions as $question) {
                        if (!DB::table('branches')
                            ->where('question_id', $question->id)
                            ->first()) {
                            $question_to_delete = Question::find($question->id);
                            $question_to_delete = $question_to_delete->toArray();

                            $seed_to_delete = Seed::find($question->id);
                            if ($seed_to_delete) {
                                $seed_to_delete = $seed_to_delete->toArray();
                            }

                            $error = 'question';


                            $question_tag_to_delete = DB::table('question_tag')
                                ->where('question_id', $question->id)
                                ->first();
                            if ($question_tag_to_delete) {
                                $question_tag_to_delete = json_decode(json_encode($question_tag_to_delete), true);

                                $question_to_delete['created_at'] = $question_tag_to_delete['created_at'];
                                $question_to_delete['updated_at'] = $question_tag_to_delete['updated_at'];


                                DB::table('deleted_questions')->insert($question_to_delete);
                            }
                            if ($seed_to_delete) {
                                $error = 'seed';
                                $seed_to_delete['created_at'] = Carbon::parse($seed_to_delete['created_at']);
                                $seed_to_delete['updated_at'] = Carbon::parse($seed_to_delete['updated_at']);
                                DB::table('deleted_seeds')->insert($seed_to_delete);

                            }
                            if ($question_tag_to_delete) {
                                $error = 'question tag';
                                $question_tag_to_delete['created_at'] = Carbon::parse($question_tag_to_delete['created_at']);
                                $question_tag_to_delete['updated_at'] = Carbon::parse($question_tag_to_delete['updated_at']);

                                DB::table('deleted_question_tag')->insert($question_tag_to_delete);
                            }

                        }
                    }
                }
                DB::commit();
            }
        } catch (Exception $e) {
            DB::rollback();
            echo "$error $question->id        {$e->getMessage()}";

        }
        echo Carbon::now();
        return 0;
    }
}
