<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompiledPDFOverride extends Model
{
    protected $guarded = [];
    protected $table = 'compiled_pdf_overrides';
    public function hasCompiledPDFOverride(int $assignment_id) {
        return DB::table('compiled_pdf_overrides')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', Auth::user()->id)
            ->first();
    }
    public function hasSetPageOverride(int $assignment_id){
        return DB::table('compiled_pdf_overrides')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', Auth::user()->id)
            ->first();
    }
}
