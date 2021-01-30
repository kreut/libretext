<?php

namespace App;

use App\Exceptions\Handler;
use \Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;


class Log extends Model
{
    public function store($request)
    {
        $result['type'] = 'error';
        try {
            $this->user_id = Auth::user()->id;
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
