<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Question;
use App\ReportToggle;
use Exception;
use Illuminate\Support\Facades\Gate;

class ReportToggleController extends Controller
{
    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param string $item
     * @param ReportToggle $reportToggle
     * @return array
     * @throws Exception
     */
    public function update(Assignment   $assignment,
                           Question     $question,
                           string       $item,
                           ReportToggle $reportToggle): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        if (!in_array($item,['section_scores','comments','criteria'])){
            $response['message'] = "$item is not a valid report toggle.";
            return $response;
        }
        try {
            $report_toggle = $reportToggle->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            if (!$report_toggle) {
                $report_toggle = new ReportToggle();
                $report_toggle->assignment_id = $assignment->id;
                $report_toggle->question_id = $question->id;
                $report_toggle->{$item} = 1;
            } else {
                $report_toggle->{$item} = !$report_toggle->{$item};
            }
            $report_toggle->save();
            $response['type'] = $report_toggle->{$item} ? 'success' : 'info';
            $view = $report_toggle->{$item} ? 'shown' : 'hidden';
            $item = str_replace('_', ' ', $item);
            $response['message'] = "The $item are now $view.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to update the toggle values.  Please try again.";
        }
        return $response;
    }

    public function show(Assignment   $assignment,
                         Question     $question,
                         string       $item,
                         ReportToggle $reportToggle): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $report_toggle = $reportToggle->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            $toggle_value = $report_toggle ? $report_toggle->{$item} : 0;

            $response['type'] = 'success';
            $response['toggle_value'] = $toggle_value;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to get the $item toggle value.  Please try again.";
        }
        return $response;
    }
}
