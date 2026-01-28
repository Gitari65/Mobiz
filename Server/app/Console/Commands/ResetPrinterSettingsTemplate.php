<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PrinterSettings;

class ResetPrinterSettingsTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'printer:reset-template';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset printer settings to new generic templates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Resetting printer settings templates...');

        $oldHeader = "🌾 AGROVET SUPPLIES\nYour Trusted Agricultural Partner\n\n📍 Kerugoya, Kirinyaga County\n\n📞 Contact: +254-XXX-XXXX";
        $oldFooter = "Thank you for your business!\n\n* Return policy: 7 days with receipt\n\n💚 Quality products, reliable service";

        $newHeader = "[Business Name]\n[Tagline or Motto]\n\n📍 [Town], [County]\n\n📞 Contact: +254-XXX-XXXX";
        $newFooter = "Thank you for your business!\n\nReturn policy: 7 days with receipt\n\n💚 [Motto or Slogan]";

        $updated = PrinterSettings::where('header_message', $oldHeader)
            ->where('footer_message', $oldFooter)
            ->update([
                'header_message' => $newHeader,
                'footer_message' => $newFooter,
            ]);

        $this->info("Updated {$updated} printer settings to new generic templates.");
        return Command::SUCCESS;
    }
}
