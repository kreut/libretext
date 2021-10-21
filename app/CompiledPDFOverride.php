<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompiledPDFOverride extends Model
{
    protected $guarded = [];
    protected $table = 'compiled_pdf_overrides';

    /**
     * @param int $assignment_id
     * @param AssignmentLevelOverride $assignmentLevelOverride
     * @return bool
     */
    public function hasCompiledPDFOverride(int $assignment_id, AssignmentLevelOverride $assignmentLevelOverride): bool
    {
        return DB::table('compiled_pdf_overrides')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', Auth::user()->id)
            ->exists()
            || $assignmentLevelOverride->hasAssignmentLevelOverride($assignment_id);
    }

    /**
     * @param int $assignment_id
     * @param AssignmentLevelOverride $assignmentLevelOverride
     * @return bool
     */
    public function hasSetPageOverride(int $assignment_id, AssignmentLevelOverride $assignmentLevelOverride): bool
    {
        return DB::table('compiled_pdf_overrides')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', Auth::user()->id)
            ->exists()
            || $assignmentLevelOverride->hasAssignmentLevelOverride($assignment_id);
    }
}
