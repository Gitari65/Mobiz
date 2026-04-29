<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding default message templates...');

        $defaults = MessageTemplate::getDefaultTemplates();

        foreach ($defaults as $template) {
            MessageTemplate::create([
                ...$template,
                'company_id' => 1, // Change to match actual company IDs if different
            ]);
        }

        $this->command->info('✓ ' . count($defaults) . ' message templates seeded');
    }
}
