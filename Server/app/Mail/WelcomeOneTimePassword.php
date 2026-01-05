<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class WelcomeOneTimePassword extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $otp;

    public function __construct(User $user, string $otp)
    {
        $this->user = $user;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Welcome to MOBIz — Your Temporary Password')
                    ->view('emails.welcome_otp')
                    ->with([
                        'name' => $this->user->name,
                        'otp' => $this->otp,
                        'company' => $this->user->company?->name ?? null,
                    ]);
    }
}
