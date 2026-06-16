<?php

namespace App\Console\Commands\OneTimers\Qti;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class convertNumericalFormattedQuestionTypeToSingleNumerical extends Command
{
    protected $signature = 'qti:convert-numerical-to-single-numerical {--undo : Revert Single Numerical back to Numerical}';

    protected $description = 'Convert all formatted_question_type values from Numerical to Single Numerical (use --undo to revert)';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $undo = $this->option('undo');

        $from = $undo ? 'Single Numerical' : 'Numerical';
        $to   = $undo ? 'Numerical' : 'Single Numerical';

        $count = DB::table('formatted_question_types')
            ->join('questions', 'formatted_question_types.question_id', '=', 'questions.id')
            ->where('formatted_question_type', $from)
            ->where('qti_json_type', 'numerical')
            ->count();

        if ($count === 0) {
            $this->info("No records found with formatted_question_type = '{$from}'. Nothing to do.");
            return 0;
        }


        DB::table('formatted_question_types')
            ->join('questions', 'formatted_question_types.question_id', '=', 'questions.id')
            ->where('formatted_question_type', $from)
            ->where('qti_json_type', 'numerical')
            ->update(['formatted_question_type' => $to]);

        $this->info("Done. {$count} record(s) updated from '{$from}' to '{$to}'.");

        return 0;
    }
}
