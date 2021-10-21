<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssignmentLevelOverride extends Model
{
    protected $guarded = [];

    /**
     * @param int $assignment_id
     * @return bool
     */
    public function hasAssignmentLevelOverride(int $assignment_id): bool
    {
        return  DB::table('assignment_level_overrides')
            ->select('id')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', Auth::user()->id)
            ->exists();
    }
}
