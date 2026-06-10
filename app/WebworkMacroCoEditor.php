<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WebworkMacroCoEditor extends Model
{
    protected $table = 'webwork_macro_co_editors';

    protected $fillable = ['webwork_macro_id', 'user_id'];

    public function macro()
    {
        return $this->belongsTo(WebworkMacro::class, 'webwork_macro_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check whether $userId is a co-editor of $macroId.
     */
    public static function isCoEditor(int $userId, int $macroId): bool
    {
        return self::where('webwork_macro_id', $macroId)
            ->where('user_id', $userId)
            ->exists();
    }
}
