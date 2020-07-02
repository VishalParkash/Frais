<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SocialMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $user;

    public function __construct($user)
    {
        // die('in construct');
        $this->user = $user;
    }

    public function build()
    {
        // print_r($this->user);die;
        return $this->from('info@expenserocket.com', 'Expense Rocket')
                    ->subject('Expense Rocket: Welcome mail')
                    ->view('emails.social')
                    ->with([
                  'user' => $this->user,
                ]);
    }
}
