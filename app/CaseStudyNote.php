<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CaseStudyNote extends Model
{
    protected $guarded = [];

    public function updateBasedOnVersion($request, $version){

        foreach ($request->case_study_notes as $case_study_note) {
            if ($case_study_note['version'] === $version) {
                CaseStudyNote::updateOrCreate(
                    ['assignment_id' => $request->assignment_id,
                        'type' => $case_study_note['type'],
                        'version' => $version],
                    ['text' => $case_study_note['text']]
                );
            }
        }
    }
    /**
     * @param string $type
     * @return string
     */
    public function formatType(string $type): string
    {
        $formatted_type = str_replace('_', ' ', $type);
        $formatted_type = ucfirst($formatted_type);
        if ($formatted_type === 'mar') {
            $formatted_type = 'MAR';
        }
        return $formatted_type;
    }

    /**
     * @return string[]
     */
    public function validCaseStudyNotes(): array
    {
        return ['history_and_physical',
            'progress_notes',
            'vital_signs',
            'lab_results',
            'provider_orders',
            'mar',
            'handoff_report'];
    }
}
