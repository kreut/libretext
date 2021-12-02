<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactGrader extends Mailable
{
    use Queueable, SerializesModels;


    public $text;
    public $from;
    public $name;
    public $email;
    public $link;


    /**
     * ContactGrader constructor.
     * @param string $subject
     * @param string $text
     * @param string $email
     * @param string $name
     * @param string $link
     */
    public function __construct(string $subject, string $text, string $email, string $name, string $link)
    {
        $this->subject = $subject;
        $this->text = $text;
        $this->email = $email;
        $this->name = $name;
        $this->link = $link;


    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->from('adapt@noreply.libretexts.org','ADAPT')
            ->view('emails.contact_grader')
            ->replyTo($this->email, $this->name)
            ->with( ['name' => $this->name,
                'subject' => $this->subject,
                'text' => $this->text,
                'link' => $this->link]);
    }
}
