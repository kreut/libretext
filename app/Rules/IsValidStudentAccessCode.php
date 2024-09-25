<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IsValidStudentAccessCode implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $sectionExists = DB::table('sections')->where('access_code', $value)->exists();
        $invitationExists = DB::table('pending_course_invitations')->where('access_code', $value)->exists();

        return $sectionExists || $invitationExists;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The access code is not valid.';
    }
}
