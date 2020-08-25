<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\inviteTa;

class InvitationController extends Controller
{
    public function emailInvitation(Request $request)
    {
        //check that it's the user course
        //check that it's a valid email
        //send off the email

        $to_name = 'Eric Kean';
        $to_email = 'kreut@hotmail.com';


        Mail::to([$to_email])->send(new inviteTa());
    }
}
