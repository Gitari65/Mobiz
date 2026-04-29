<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Company;
use App\Models\User;

class CompanyVerified extends Mailable
{
    use Queueable, SerializesModels;

    public $company;
    public $user;
    public $defaultPassword;

    public function __construct(Company $company, User $user, string $defaultPassword)
    {
        $this->company = $company;
        $this->user = $user;
        $this->defaultPassword = $defaultPassword;
    }

    public function build()
    {
        return $this->subject('Your Company Account Has Been Approved - ' . $this->company->name)
                    ->view('emails.company_verified')
                    ->with([
                        'companyName' => $this->company->name,
                        'ownerName' => $this->user->name,
                        'email' => $this->user->email,
                        'defaultPassword' => $this->defaultPassword,
                    ]);
    }
}
