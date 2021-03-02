<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Http\Request;

class CookieController extends Controller
{
    /**
     * @param string $questionView
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws Exception
     */
    public function setQuestionView(string $questionView)
    {
        $response['type'] = 'error';
        $cookie = cookie()->forever('question_view', 'basic');
        try {
            $question_view = ($questionView === 'basic') ? 'advanced' : 'basic';
            $cookie = cookie()->forever('question_view', $question_view);
            $response['type'] = 'info';
            $response['message'] = "You have successfully switched the question view to <strong>$question_view.</strong>";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error switching views. Please try again or contact us for assistance.";
        }
        return response($response)->withCookie($cookie);
    }
}
