<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Extension extends Model
{
    protected $fillable = ['user_id', 'assignment_id', 'extension'];

    public function getUserExtensionsByAssignment($user)
    {
        $extensions = $user->extensions;

        $extensions_by_assignment = [];
        if ($extensions->isNotEmpty()) {
            foreach ($extensions as $extension) {
                $extensions_by_assignment[$extension->assignment_id] = $extension->extension;
            }
        }

        return $extensions_by_assignment;
    }

    public function getAssignmentExtensionByUser(Assignment $assignment, User $user)
    {
        $extension = DB::table('extensions')->where('user_id', $user->id)
            ->where('assignment_id', $assignment->id)
            ->first();
        if ($extension){
            return $extension->extension;
        }
        return false;
    }
}
