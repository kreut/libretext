<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MindTouchEventController extends Controller
{
    public function update(Request $request) {
        Log:info('made it!');
        Log::info( $request->page_id );

    }
}
