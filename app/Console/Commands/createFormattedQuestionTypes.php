<?php

namespace App\Console\Commands;

use App\Question;
use App\FormattedQuestionType;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

ini_set('memory_limit', '4G');

class createFormattedQuestionTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:formattedQuestionTypes {technology?}';

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
    public function handle(FormattedQuestionType $questionFormat)
    {
        $technology = $this->argument('technology');
        try {
            $questions = $technology ?
                Question::where('technology', $technology)->get()
                : Question::where('technology', '<>', 'webwork')->where('technology', '<>', 'imathas')->get();
            if ($technology === 'imathas') {
                $domain = app()->environment('local') ? "dev2.imathas.libretexts.org" : "imathas.libretexts.org";
                $domain = "imathas.libretexts.org";
                $response = Http::get("https://$domain/imathas/adapt/question_types.php");

                if ($response->successful()) {
                    echo "Got response";
                    $data = $response->body(); // or $response->json() if you expect a JSON response

                    $data = json_decode($data, 1);
                    foreach ($data['question_types'] as $value) {
                        if ($value['qtype'] === 'multipart') {
                            $types = [];
                            $pattern = '/\$anstypes\s*=\s*array\(([^)]*)\)/';
                            if (preg_match($pattern, $value['control'], $matches)) {
                                $array_string = $matches[1];
                                $anstypes = array_map('trim', explode(',', $array_string));
                                $anstypes = array_map(function ($value) {
                                    return trim($value, '"\'');
                                }, $anstypes);
                                $anstypes = array_unique($anstypes);
                                foreach ($anstypes as $type) {
                                    $types[] = $type;
                                }
                                $question_formatted_types_by_id[$value['id']] = $types;
                            }
                        } else {
                            $question_formatted_types_by_id[$value['id']] = [$value['qtype']];
                        }
                    }
                    echo "Organized imathas";
                } else {
                    echo "Error getting the formats from iMathAS";
                    exit;
                }

            }

            foreach ($questions as $question) {
                $formatted_question_type = null;
                if (in_array($question->technology, ['imathas', 'webwork'])) {
                    switch ($question->technology) {
                        case('imathas'):
                            $question_formatted_types = $question_formatted_types_by_id[ltrim($question->technology_id, '0')] ?? null;
                            if ($question_formatted_types) {
                                foreach ($question_formatted_types as $question_format) {
                                    $formatted_question_type = $question->imathasFormattedType($question_format);
                                    echo $formatted_question_type;
                                    $this->_addFormattedQuestionType($question, $questionFormat, $formatted_question_type);
                                }
                            } else {
                                echo "No results for $question->technology_id\r\n";
                            }
                            break;
                        case('webwork'):
                            if ($question->webwork_code) {
                                $formatted_question_types = $question->webworkFormattedType();
                                if ($formatted_question_types) {
                                    foreach ($formatted_question_types as $formatted_question_type) {
                                        $this->_addFormattedQuestionType($question, $questionFormat, $formatted_question_type);
                                    }
                                }
                            }
                    }
                } else {
                    $this->_addFormattedQuestionType($question, $questionFormat, $formatted_question_type);
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 0;
    }

    /**
     * @param Question $question
     * @param FormattedQuestionType $questionFormat
     * @param $formatted_question_type
     * @return void
     */
    private function _addFormattedQuestionType(Question $question, FormattedQuestionType $questionFormat, $formatted_question_type = null): void
    {
        if (!$formatted_question_type) {
            $formatted_question_type = $question->initFormattedQuestionTypes();
        }
        if ($formatted_question_type) {
            if (!$questionFormat
                ->where('question_id', $question->id)
                ->where('formatted_question_type', $formatted_question_type)->exists()) {
                DB::table('formatted_question_types')->insert([
                    'question_id' => $question->id,
                    'formatted_question_type' => $formatted_question_type,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            if (!DB::table('formatted_question_type_technology')
                ->where('technology', $question->technology)
                ->where('formatted_question_type', $formatted_question_type)->exists()) {
                DB::table('formatted_question_type_technology')->insert([
                    'technology' => $question->technology,
                    'formatted_question_type' => $formatted_question_type,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

    }
}
