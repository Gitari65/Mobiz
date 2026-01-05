<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class PasswordResetNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $tempPassword;

    public function __construct(User $user, string $tempPassword)
    {
        $this->user = $user;
        $this->tempPassword = $tempPassword;
    }

    public function build()
    {
        return $this->subject('Your password has been reset')
                    ->view('emails.password_reset')
                    ->with([
                        'name' => $this->user->name,
                        'email' => $this->user->email,
                        'tempPassword' => $this->tempPassword,
                        'mustChange' => $this->user->must_change_password ?? false
                    ]);
    }
}
