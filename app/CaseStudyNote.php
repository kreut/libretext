<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CaseStudyNote extends Model
{
    protected $guarded = [];

    /**
     * @param string $type
     * @return string
     */
    public function formatType(string $type): string
    {
        $formatted_type= str_replace('_', ' ', $type);
        $formatted_type = ucfirst($formatted_type);
        if ($formatted_type === 'mar') {
            $formatted_type= 'MAR';
        }
        return $formatted_type;
    }
}
