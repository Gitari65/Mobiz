<?php

namespace App\Http\Controllers;

use App\Models\PrinterSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PrinterSettingsController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
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
                'invoice_title' => 'INVOICE',
                'invoice_subtitle' => 'Thank you for your business',
                'invoice_footer_note' => 'Payment due as per agreed terms',
                'invoice_show_logo' => true,
            ]
        );

        return response()->json(array_merge($settings->toArray(), [
            'receipt_logo_url' => $settings->receipt_logo_path ? Storage::url($settings->receipt_logo_path) : null,
        ]));
    }

    public function update(Request $request)
    {
        $user = $request->user();
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
            'invoice_title' => 'nullable|string|max:120',
            'invoice_subtitle' => 'nullable|string|max:180',
            'invoice_footer_note' => 'nullable|string|max:300',
            'invoice_show_logo' => 'boolean',
        ]);

        $settings = PrinterSettings::firstOrCreate(['company_id' => $user->company_id]);
        $settings->update($validated);

        return response()->json(array_merge($settings->toArray(), [
            'receipt_logo_url' => $settings->receipt_logo_path ? Storage::url($settings->receipt_logo_path) : null,
        ]));
    }

    public function uploadLogo(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $settings = PrinterSettings::firstOrCreate(['company_id' => $user->company_id]);

        if ($settings->receipt_logo_path) {
            Storage::disk('public')->delete($settings->receipt_logo_path);
        }

        $file = $validated['logo'];
        $filename = 'printer_logo_' . $user->company_id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('receipt_logos', $filename, 'public');

        $settings->receipt_logo_path = $path;
        $settings->save();

        return response()->json([
            'message' => 'Logo uploaded successfully',
            'receipt_logo_path' => $path,
            'receipt_logo_url' => Storage::url($path),
        ]);
    }

    public function removeLogo(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $settings = PrinterSettings::where('company_id', $user->company_id)->first();
        if ($settings && $settings->receipt_logo_path) {
            Storage::disk('public')->delete($settings->receipt_logo_path);
            $settings->receipt_logo_path = null;
            $settings->save();
        }

        return response()->json(['message' => 'Logo removed successfully']);
    }
}
