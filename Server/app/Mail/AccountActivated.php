<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class AccountActivated extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $temporaryPassword;

    public function __construct(User $user, string $temporaryPassword = null)
    {
        $this->user = $user;
        $this->temporaryPassword = $temporaryPassword;
    }

    public function build()
    {
        return $this->subject('Your Account Has Been Activated')
                    ->view('emails.account_activated')
                    ->with([
                        'name' => $this->user->name,
                        'email' => $this->user->email,
                        'company' => $this->user->company?->name ?? 'MOBIz',
                        'temporaryPassword' => $this->temporaryPassword,
                    ]);
    }
}
