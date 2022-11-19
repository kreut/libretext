<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Framework extends Model
{
    function setProperties($request, $data)
    {
        $this->title = $data['title'];
        $this->descriptor_type = $data['descriptor_type'];
        $this->description = $data['description'];
        $this->author = $data['author'];
        $this->license = $data['license'];
        $this->license_version = $request->license_version;
        $this->source_url = $data['source_url'];
        $this->user_id = $request->user()->id;
    }
}
