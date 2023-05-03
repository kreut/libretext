<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReportToggle extends Model
{
    protected $guarded = [];

    public function getShownReportItems($items, $report_toggles)
    {
        //handles both the rubric criteria and the submission

        foreach ($items as $item) {
            if (!$report_toggles['section_scores']) {
                if (is_object($item)) {
                    $item->score = '';
                    $item->custom_score= '';
                } else {
                    unset($item['score']);
                }
            }
            if (!$report_toggles['comments']) {
                if (is_object($item)) {
                    $item->comments = '';
                    $item->custom_feedback = '';
                } else {
                    unset($item['comments']);
                }
            }
            if (!$report_toggles['criteria']) {
                if (is_object($item)) {
                    $item->criteria = '';
                } else {
                    unset($item['criteria']);
                }
            }
        }
        return $items;

    }
}
