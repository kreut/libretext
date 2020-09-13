<?php

namespace App\Http\Controllers;


use App\Exceptions\Handler;
use \Exception;


use App\Http\Requests\EmailContactUs;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactUs;

class ContactUsController extends Controller
{
    public function contactUs(EmailContactUs $request)
    {
        $response['type'] = 'error';
        $data = $request->validated();

        try {
            Mail::to('adapt@libretexts.org')
                ->send(new ContactUs($data['subject'], $data['text'], $data['email'], $data['name']));
            $response['type'] = 'success';
            $response['message'] = 'Thank you for your message!  Please expect a response within 1 business day.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error sending the email.  Please try again.";
        }
        return $response;


    }
}
