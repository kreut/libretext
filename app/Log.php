<?php

namespace App;

use App\Exceptions\Handler;
use \Exception;
use Illuminate\Database\Eloquent\Model;



class Log extends Model
{
    public function store($request)
    {
        $result['type'] = 'error';
        try {
            $this->user_id = $request->user()->id;
            $this->action = $request->action;
            $this->data = $request->data ? json_encode($request->data) : '';
            $this->save();
            $result['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
        return $result;
    }
}
