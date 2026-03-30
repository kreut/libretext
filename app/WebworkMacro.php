<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class WebworkMacro extends Model
{

    protected $fillable = [
        'user_id',
        'source',        // 'official' | 'custom'
        'name',
        'description',
        'macro'        // source code (custom) OR a GitHub URL (official)
    ];

    /**
     * @param string $name
     * @return string
     * @throws Exception
     */
    public function getSource(string $name): string
    {
        $webwork_base_url = app()->environment('production')
            ? 'https://opl.libretexts.org'
            : 'https://staging-opl.libretexts.org';
        $webwork_response = Http::get("$webwork_base_url/api/macros/by-name/$name");
        if ($webwork_response->successful()) {
            return $webwork_response->body();
        } else {
            throw new Exception("Could not get the source for $name");
        }
    }

    /**
     * The instructor who created this macro (null for official macros).
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * All revisions of this macro.
     */
    public function revisions()
    {
        return $this->hasMany(WebworkMacroRevision::class)->orderBy('revision_number');
    }

    /**
     * Questions that reference this macro.
     */
    public function questions()
    {
        return $this->belongsToMany(
            Question::class,
            'question_webwork_macros',
            'webwork_macro_id',
            'question_id'
        )->withPivot('question_revision_id')->withTimestamps();
    }

    /**
     * Whether the macro field holds a URL rather than source code.
     * Official macros store a GitHub URL; custom macros store Perl source.
     */
    public function macroIsLink(): bool
    {
        return filter_var($this->macro, FILTER_VALIDATE_URL) !== false;
    }
}
