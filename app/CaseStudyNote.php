<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CaseStudyNote extends Model
{
    protected $guarded = [];

    /**
     * @param $assignment
     * @return array
     */
    public function getByType($assignment): array
    {
        $case_study_notes = $this->where('assignment_id', $assignment->id)
            ->orderBy('first_application','DESC')
            ->get();
        if ($case_study_notes->isNotEmpty()) {
            foreach ($case_study_notes as $key => $value) {
                $case_study_notes[$key]['expanded'] = false;
            }
        }
        $case_study_notes_by_type = [];
        $types = [];
        foreach ($case_study_notes as $item) {
            if (!in_array($item->type, $types)) {
                $case_study_notes_by_type[] = ['type' => $item->type, 'notes' => []];
            }
            $types[] = $item->type;
        }
        foreach ($case_study_notes as $item) {
            foreach ($case_study_notes_by_type as $key => $value) {
                if ($item->type === $value['type']) {
                    $case_study_notes_by_type[$key]['notes'][] = $item;
                }
            }
        }
        return $case_study_notes_by_type;
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
