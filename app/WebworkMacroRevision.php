<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WebworkMacroRevision extends Model
{
    protected $fillable = [
        'webwork_macro_id',
        'name',
        'description',
        'macro',
        'edited_by_user_id',
        'revision_number',
        'reason_for_edit',
    ];

    public function macro()
    {
        return $this->belongsTo(WebworkMacro::class, 'webwork_macro_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by_user_id');
    }
}
