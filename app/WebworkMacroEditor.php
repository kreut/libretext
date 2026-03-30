<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WebworkMacroEditor extends Model
{
    protected $fillable = [
        'user_id',
        'granted_by_user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    /**
     * Check if a given user_id is a macro editor.
     */
    public static function isEditor(int $user_id): bool
    {
        return static::where('user_id', $user_id)->exists();
    }
}
