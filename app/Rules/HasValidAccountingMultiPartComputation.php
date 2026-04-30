<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class HasValidAccountingMultiPartComputation implements Rule
{
    private string $errorMessage = '';

    public function passes($attribute, $value): bool
    {
        $generalErrors = [];
        $specific = []; // keyed by [ti][ri][ci][field]

        $qtiJson = is_array($value) ? $value : json_decode($value, true);

        if (!$qtiJson || !is_array($qtiJson)) {
            $this->errorMessage = json_encode(['general' => 'Invalid question data.', 'specific' => []]);
            return false;
        }

        $tables = $qtiJson['tables'] ?? [];

        if (empty($tables)) {
            $this->errorMessage = json_encode(['general' => 'At least one table is required.', 'specific' => []]);
            return false;
        }

        $questionHasAnswerCell = false;

        foreach ($tables as $ti => $table) {
            $tableNum = $ti + 1;
            $columns = $table['columns'] ?? [];
            $rows = $table['rows'] ?? [];
            $tableType = $table['tableType'] ?? 'table';

            if (empty($columns)) {
                $generalErrors[] = "Table $tableNum must have at least one column.";
                continue;
            }

            if (empty($rows)) {
                $generalErrors[] = "Table $tableNum must have at least one row.";
                continue;
            }

            $tableHasAnswerCell = false;

            foreach ($rows as $ri => $row) {
                $rowType = $row['rowType'] ?? 'data';

                // Defensive: lineItems tables shouldn't have row headers
                if ($tableType === 'lineItems' && $rowType === 'rowheader') {
                    continue;
                }

                if ($rowType === 'instruction') {
                    $instructionText = trim($row['instructionText'] ?? '');
                    if ($instructionText === '') {
                        $specific[$ti][$ri]['instructionText'] = 'Instruction text is required.';
                    }
                    continue;
                }

                if ($rowType === 'rowheader') {
                    continue;
                }

                // Data row
                $cells = $row['cells'] ?? [];

                foreach ($cells as $ci => $cell) {
                    $mode = $cell['mode'] ?? 'blank';

                    if ($mode === 'blank') {
                        continue;
                    }

                    if ($mode === 'display') {
                        $displayValue = trim($cell['value'] ?? '');
                        if ($displayValue === '') {
                            $specific[$ti][$ri][$ci]['value'] = 'Display cell must have a value.';
                        }
                        continue;
                    }

                    if ($mode === 'answer') {
                        $tableHasAnswerCell = true;
                        $questionHasAnswerCell = true;
                        $answerType = $cell['answerType'] ?? '';
                        $rawValue = trim($cell['value'] ?? '');

                        switch ($answerType) {
                            case 'dollar':
                                if ($rawValue === '') {
                                    $specific[$ti][$ri][$ci]['value'] = 'A correct answer is required.';
                                } elseif (!is_numeric(str_replace([',', '$', ' '], '', $rawValue))) {
                                    $specific[$ti][$ri][$ci]['value'] = 'Dollar amount must be numeric.';
                                }
                                $dollarRounding = $cell['dollarRounding'] ?? '';
                                if (!in_array($dollarRounding, ['dollar', 'cent'])) {
                                    $specific[$ti][$ri][$ci]['dollarRounding'] = 'Rounding must be set to nearest dollar or cent.';
                                }
                                break;

                            case 'general':
                                if ($rawValue === '') {
                                    $specific[$ti][$ri][$ci]['value'] = 'A correct answer is required.';
                                } elseif (!is_numeric(str_replace([',', ' '], '', $rawValue))) {
                                    $specific[$ti][$ri][$ci]['value'] = 'Value must be numeric.';
                                }
                                $decimalPlaces = $cell['decimalPlaces'] ?? null;
                                if (!is_int($decimalPlaces) && !ctype_digit((string) $decimalPlaces)) {
                                    $specific[$ti][$ri][$ci]['decimalPlaces'] = 'Decimal places must be set.';
                                } elseif ((int) $decimalPlaces < 0 || (int) $decimalPlaces > 6) {
                                    $specific[$ti][$ri][$ci]['decimalPlaces'] = 'Decimal places must be between 0 and 6.';
                                }
                                break;

                            case 'percentage':
                            case 'ratio':
                                if ($rawValue === '') {
                                    $specific[$ti][$ri][$ci]['value'] = 'A correct answer is required.';
                                } elseif (!is_numeric(str_replace([',', ' '], '', $rawValue))) {
                                    $specific[$ti][$ri][$ci]['value'] = ucfirst($answerType) . ' value must be numeric.';
                                }
                                $decimalPlaces = $cell['decimalPlaces'] ?? null;
                                if (!is_int($decimalPlaces) && !ctype_digit((string) $decimalPlaces)) {
                                    $specific[$ti][$ri][$ci]['decimalPlaces'] = 'Decimal places must be set.';
                                } elseif ((int) $decimalPlaces < 0 || (int) $decimalPlaces > 6) {
                                    $specific[$ti][$ri][$ci]['decimalPlaces'] = 'Decimal places must be between 0 and 6.';
                                }
                                break;

                            case 'custom':
                                if ($rawValue === '') {
                                    $specific[$ti][$ri][$ci]['value'] = 'A correct answer is required.';
                                } elseif (!is_numeric(str_replace([',', ' '], '', $rawValue))) {
                                    $specific[$ti][$ri][$ci]['value'] = 'Value must be numeric.';
                                }
                                $customUnit = trim($cell['customUnit'] ?? '');
                                if ($customUnit === '') {
                                    $specific[$ti][$ri][$ci]['customUnit'] = 'A unit label is required for custom type.';
                                }
                                $decimalPlaces = $cell['decimalPlaces'] ?? null;
                                if (!is_int($decimalPlaces) && !ctype_digit((string) $decimalPlaces)) {
                                    $specific[$ti][$ri][$ci]['decimalPlaces'] = 'Decimal places must be set.';
                                } elseif ((int) $decimalPlaces < 0 || (int) $decimalPlaces > 6) {
                                    $specific[$ti][$ri][$ci]['decimalPlaces'] = 'Decimal places must be between 0 and 6.';
                                }
                                break;

                            case 'dropdown':
                                $options = $cell['dropdownOptions'] ?? [];
                                $nonEmptyOptions = array_filter($options, fn($o) => trim($o) !== '');

                                if (empty($options) || count($nonEmptyOptions) < 2) {
                                    $specific[$ti][$ri][$ci]['dropdownOptions'] = 'At least 2 options are required.';
                                } else {
                                    // Check for empty options
                                    foreach ($options as $oi => $opt) {
                                        if (trim($opt) === '') {
                                            $specific[$ti][$ri][$ci]['dropdownOptions'] = 'All options must have text.';
                                            break;
                                        }
                                    }
                                    // Check for duplicates
                                    if (count($nonEmptyOptions) !== count(array_unique($nonEmptyOptions))) {
                                        $specific[$ti][$ri][$ci]['dropdownOptions'] = 'Options must be unique.';
                                    }
                                }

                                // Correct answer must be set and match an option
                                $correctValue = trim($cell['value'] ?? '');
                                if ($correctValue === '') {
                                    $specific[$ti][$ri][$ci]['value'] = 'Please select a correct answer.';
                                } elseif (!in_array($correctValue, $options)) {
                                    $specific[$ti][$ri][$ci]['value'] = 'The correct answer must match one of the options.';
                                }
                                break;

                            default:
                                $specific[$ti][$ri][$ci]['value'] = 'Unknown answer type.';
                        }
                    }
                }
            }

            if (!$tableHasAnswerCell) {
                $generalErrors[] = "Table $tableNum must have at least one answer cell.";
            }
        }

        if (!$questionHasAnswerCell) {
            $generalErrors[] = 'The question must have at least one answer cell for students to fill in.';
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
