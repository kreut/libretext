<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SiteMap extends Model
{
    public function init() {
        $this->getSiteMaps();
    }

    public function getSiteMaps(){

        echo "sdfdf";
    }
}
