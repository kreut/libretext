<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class HasValidAccountingReport implements Rule
{
    private string $errorMessage = '';

    public function passes($attribute, $value): bool
    {
        $generalErrors = [];
        $specific = []; // keyed by row index, then cell index or 'header'

        $qtiJson = is_array($value) ? $value : json_decode($value, true);

        if (!$qtiJson || !is_array($qtiJson)) {
            $this->errorMessage = json_encode(['general' => 'Invalid report data.', 'specific' => []]);
            return false;
        }

        // Validate columns
        $columns = $qtiJson['columns'] ?? [];
        if (empty($columns)) {
            $generalErrors[] = 'The report must have at least one column.';
        }

        $columnCount = count($columns);

        foreach ($columns as $ci => $col) {
            $colNum = $ci + 1;
            if (($col['type'] ?? '') === 'text'
                && ($col['textInputMode'] ?? '') === 'dropdown'
                && (empty($col['dropdownOptions']) || count($col['dropdownOptions']) < 2)
            ) {
                $generalErrors[] = "Column $colNum is set to dropdown but has fewer than 2 options.";
            }
        }

        // Validate rows
        $rows = $qtiJson['rows'] ?? [];
        if (empty($rows)) {
            $generalErrors[] = 'The report must have at least one row.';
            $this->errorMessage = json_encode([
                'general' => implode(' ', $generalErrors),
                'specific' => []
            ]);
            return false;
        }

        $hasDataRow = false;
        $hasAnswerCell = false;
        $hasContext = false;

        foreach ($rows as $ri => $row) {
            if (!empty($row['isHeader'])) {
                if (empty(trim($row['headerText'] ?? ''))) {
                    $specific[$ri]['header'] = 'Section header text is required.';
                } else {
                    $hasContext = true;
                }
                continue;
            }

            // Data row
            $hasDataRow = true;
            $cells = $row['cells'] ?? [];

            if (count($cells) !== $columnCount) {
                $rowNum = $ri + 1;
                $generalErrors[] = "Row $rowNum: expected $columnCount cells but found " . count($cells) . ".";
                continue;
            }

            foreach ($cells as $ci => $cell) {
                $mode = $cell['mode'] ?? 'blank';
                $cellValue = trim($cell['value'] ?? '');
                $colType = $columns[$ci]['type'] ?? 'text';

                if ($mode === 'answer') {
                    $hasAnswerCell = true;
                    if ($cellValue === '') {
                        $specific[$ri][$ci]['value'] = 'An expected answer is required.';
                    } elseif ($colType === 'numeric' && !is_numeric(str_replace(',', '', $cellValue))) {
                        $specific[$ri][$ci]['value'] = 'Value must be numeric.';
                    }
                }

                if ($mode === 'display') {
                    $hasContext = true;
                    if ($cellValue === '') {
                        $specific[$ri][$ci]['value'] = 'Display cell must have a value.';
                    } elseif ($colType === 'numeric' && !is_numeric(str_replace(',', '', $cellValue))) {
                        $specific[$ri][$ci]['value'] = 'Value must be numeric.';
                    }
                }
            }
        }

        if (!$hasDataRow) {
            $generalErrors[] = 'The report must have at least one data row (not just section headers).';
        }

        if (!$hasAnswerCell) {
            $generalErrors[] = 'The report must have at least one answer cell for students to fill in.';
        }

        if (!$hasContext) {
            $generalErrors[] = 'The report must have at least one display cell or section header to provide context for students.';
        }

        if (!empty($generalErrors) || !empty($specific)) {
            $this->errorMessage = json_encode([
                'general' => !empty($generalErrors) ? implode(' ', $generalErrors) : '',
                'specific' => $specific
            ]);
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return $this->errorMessage;
    }
}
