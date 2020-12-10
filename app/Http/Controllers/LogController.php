<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Log;

class LogController extends Controller
{
    public function store(Request $request, Log $log)
    {
        return $log->store($request);

    }
}
