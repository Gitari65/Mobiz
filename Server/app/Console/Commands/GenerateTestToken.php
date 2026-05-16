<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class GenerateTestToken extends Command
{
    protected $signature = 'mpesa:token {user_id=1}';
    protected $description = 'Generate a Sanctum API token for testing M-Pesa endpoints';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID $userId not found");
            return 1;
        }

        try {
            $token = $user->createToken('mpesa-test-token')->plainTextToken;
            
            $this->newLine();
            $this->info('✓ Test token generated successfully');
            $this->newLine();
            $this->line('User: ' . $user->email);
            $this->line('Token:');
            $this->line('');
            $this->comment($token);
            $this->line('');
            $this->line('How to use:');
            $this->line('1. Copy the token above');
            $this->line('2. In api.rest file, replace:');
            $this->line('   @authToken = your_token_here');
            $this->line('   with:');
            $this->line('   @authToken = ' . substr($token, 0, 40) . '...');
            $this->line('');
            $this->info('✓ Ready to test M-Pesa endpoints!');
            $this->newLine();
            
            return 0;
        } catch (\Throwable $e) {
            $this->error('Failed to generate token: ' . $e->getMessage());
            return 1;
        }
    }
}
