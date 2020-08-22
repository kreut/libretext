<?php

namespace App\Http\Controllers;

use App\Tag;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Tag $tag)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('viewAny', $tag);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        $response['type'] = 'success';
        $response['tags'] = DB::table('tags')
                            ->orderBy('tag')
                            ->get()
                            ->pluck('tag');
        return $response;

    }


}
