<?php

namespace App\Http\Controllers;

use App\Models\PrinterSettings;
use Illuminate\Http\Request;

class PrinterSettingsController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $settings = PrinterSettings::firstOrCreate(
            ['company_id' => $user->company_id],
            [
                'header_message' => "[Business Name]\n[Tagline or Motto]\n\n📍 [Town], [County]\n\n📞 Contact: +254-XXX-XXXX",
                'footer_message' => "Thank you for your business!\n\nReturn policy: 7 days with receipt\n\n💚 [Motto or Slogan]",
                'show_logo' => true,
                'show_taxes' => true,
                'show_discounts' => true,
                'paper_size' => '58mm',
                'font_size' => 12,
                'alignment' => 'center',
                'copies' => 1,
            ]
        );

        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'header_message' => 'nullable|string|max:500',
            'footer_message' => 'nullable|string|max:500',
            'show_logo' => 'boolean',
            'show_taxes' => 'boolean',
            'show_discounts' => 'boolean',
            'paper_size' => 'nullable|in:58mm,80mm',
            'font_size' => 'nullable|integer|min:8|max:18',
            'alignment' => 'nullable|in:left,center,right',
            'copies' => 'nullable|integer|min:1|max:5',
        ]);

        $settings = PrinterSettings::firstOrCreate(['company_id' => $user->company_id]);
        $settings->update($validated);

        return response()->json($settings);
    }
}
