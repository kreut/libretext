<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Email extends Mailable
{
    use Queueable, SerializesModels;


    public $text;
    public $from;
    public $name;
    public $email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $subject, string $text, string $email, string $name)
    {
        $this->subject = $subject;
        $this->text = $text;
        $this->email = $email;
        $this->name = $name;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->from($this->email, $this->name)
                    ->view('emails.contact_us')
                    ->replyTo($this->email, $this->name)
                    ->with([
                        'subject' => $this->subject,
                        'text' => $this->text,
            ]);
    }
}
