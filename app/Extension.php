<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
}
