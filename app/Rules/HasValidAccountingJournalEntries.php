<?php

namespace App\Rules;

use App\Helpers\Accounting;
use Illuminate\Contracts\Validation\Rule;


class HasValidAccountingJournalEntries implements Rule
{

    protected $errors = [];

    /**
     * Helper method to parse amount strings that may contain commas
     *
     * @param mixed $value
     * @return float|null
     */
    protected function parseAmount($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Convert to string and remove commas
        $sanitized = str_replace(',', '', (string) $value);

        // Check if the sanitized value is numeric
        if (!is_numeric($sanitized)) {
            return null;
        }

        return floatval($sanitized);
    }

    /**
     * Determine if the validation passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->errors = [
            'specific' => [],
            'general' => null
        ];

        // If value is a JSON string, decode it
        if (is_string($value)) {
            $value = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->errors['general'] = 'Invalid JSON format.';
                return false;
            }
        }

        // Check if entries exist and is an array
        if (!isset($value['entries']) || !is_array($value['entries'])) {
            $this->errors['general'] = 'No journal entries provided.';
            return false;
        }

        $entries = $value['entries'];

        // Require at least one entry
        if (count($entries) === 0) {
            $this->errors['general'] = 'At least one journal entry is required.';
            return false;
        }

        $hasErrors = false;

        foreach ($entries as $entryIndex => $entry) {
            $entryErrors = [];

            // Validate entry text
            if (empty($entry['entryText']) || trim($entry['entryText']) === '') {
                $entryErrors['entryText'] = 'Entry text is required.';
                $hasErrors = true;
            }

            // Validate entry description
            if (empty($entry['entryDescription']) || trim($entry['entryDescription']) === '') {
                $entryErrors['entryDescription'] = 'Entry description is required.';
                $hasErrors = true;
            }

            // Validate solution rows
            if (!isset($entry['solutionRows']) || !is_array($entry['solutionRows'])) {
                $entryErrors['solutionRows'] = ['general' => 'Solution rows are required.'];
                $hasErrors = true;
            } else {
                $solutionRows = $entry['solutionRows'];
                $rowErrors = [];

                // Require at least 2 rows
                if (count($solutionRows) < 2) {
                    $rowErrors['general'] = 'At least 2 solution rows are required.';
                    $hasErrors = true;
                } elseif (count($solutionRows) > 5) {
                    $rowErrors['general'] = 'Maximum of 5 solution rows allowed.';
                    $hasErrors = true;
                }

                $totalDebits = 0;
                $totalCredits = 0;
                $validAccountTitles = Accounting::validAccountingJournalEntries();
                foreach ($solutionRows as $rowIndex => $row) {
                    $rowFieldErrors = [];

                    // Validate account title
                    if (empty($row['accountTitle']) || trim($row['accountTitle']) === '') {
                        $rowFieldErrors['accountTitle'] = 'Account title is required.';
                        $hasErrors = true;
                    } elseif (!in_array($row['accountTitle'], $validAccountTitles)) {
                        $rowFieldErrors['accountTitle'] = 'Account title must be from the valid list of accounts.';
                        $hasErrors = true;
                    }

                    // Validate type
                    if (empty($row['type']) || !in_array($row['type'], ['debit', 'credit'])) {
                        $rowFieldErrors['type'] = 'Type must be either debit or credit.';
                        $hasErrors = true;
                    }

                    // Validate amount - use parseAmount to handle commas
                    $parsedAmount = $this->parseAmount($row['amount'] ?? null);

                    if ($parsedAmount === null) {
                        if (!isset($row['amount']) || $row['amount'] === '' || $row['amount'] === null) {
                            $rowFieldErrors['amount'] = 'Amount is required.';
                        } else {
                            $rowFieldErrors['amount'] = 'Amount must be a valid number.';
                        }
                        $hasErrors = true;
                    } elseif ($parsedAmount <= 0) {
                        $rowFieldErrors['amount'] = 'Amount must be greater than 0.';
                        $hasErrors = true;
                    } else {
                        // Calculate totals
                        if (isset($row['type'])) {
                            if ($row['type'] === 'debit') {
                                $totalDebits += $parsedAmount;
                            } elseif ($row['type'] === 'credit') {
                                $totalCredits += $parsedAmount;
                            }
                        }
                    }

                    if (!empty($rowFieldErrors)) {
                        $rowErrors[$rowIndex] = $rowFieldErrors;
                    }
                }

                // Check if debits and credits balance (within 0.01 tolerance for floating point)
                if (abs($totalDebits - $totalCredits) > 0.01 && $totalDebits > 0 && $totalCredits > 0) {
                    $rowErrors['general'] = sprintf(
                        'Entry does not balance. Debits: $%.2f, Credits: $%.2f',
                        $totalDebits,
                        $totalCredits
                    );
                    $hasErrors = true;
                }

                if (!empty($rowErrors)) {
                    $entryErrors['solutionRows'] = $rowErrors;
                }
            }

            if (!empty($entryErrors)) {
                $this->errors['specific'][$entryIndex] = $entryErrors;
            }
        }

        // Clean up empty specific errors
        if (empty($this->errors['specific'])) {
            unset($this->errors['specific']);
        }

        return !$hasErrors;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return json_encode($this->errors);
    }
}
