<?php

namespace App\Http\Controllers;

use App\Models\TaxConfiguration;
use Illuminate\Http\Request;

/**
 * Tax Configuration Controller
 * 
 * Manages tax configurations for companies in Kenya.
 * 
 * SYSTEM DEFAULTS:
 * - Kenya tax defaults (Standard VAT 16%, Zero-Rated, Exempt) are marked as is_system_default=true
 * - These are created for ALL companies during seeding
 * - Currently, system defaults can be edited (with logging) but cannot be deleted
 * 
 * TODO - FUTURE SUPER USER IMPLEMENTATION:
 * 1. Add 'is_super_user' field to users table
 * 2. Update update() method to restrict editing of system defaults to super users only
 * 3. Update destroy() method to allow super users to delete system defaults if needed
 * 4. Add middleware to check super user status
 * 5. Update frontend to show warning/disabled state for non-super users
 */
class TaxConfigurationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

                $taxes = TaxConfiguration::where(function($q) use ($user) {
                                $q->where('company_id', $user->company_id)
                                    ->orWhereNull('company_id');
                        })
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json($taxes);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tax_type' => 'required|in:VAT,Excise,Withholding,Other',
            'rate' => 'required|numeric|min:0|max:100',
            'is_inclusive' => 'required|boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $validated['company_id'] = $user->company_id;

        if ($validated['is_default'] ?? false) {
            TaxConfiguration::where('company_id', $user->company_id)->update(['is_default' => false]);
        }

        $tax = TaxConfiguration::create($validated);
        return response()->json($tax, 201);
    }

    public function show($id)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tax = TaxConfiguration::where('company_id', $user->company_id)->findOrFail($id);
        return response()->json($tax);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tax = TaxConfiguration::where('company_id', $user->company_id)->findOrFail($id);

        // TODO: Protect system defaults - only super users can edit
        // For now, allow editing but log a warning
        if ($tax->is_system_default) {
            \Log::warning('System default tax configuration being modified', [
                'tax_id' => $id,
                'user_id' => auth()->id(),
                'company_id' => $user->company_id,
            ]);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'tax_type' => 'sometimes|in:VAT,Excise,Withholding,Other',
            'rate' => 'sometimes|numeric|min:0|max:100',
            'is_inclusive' => 'sometimes|boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        if (($validated['is_default'] ?? false) && !$tax->is_default) {
            TaxConfiguration::where('company_id', $user->company_id)
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $tax->update($validated);
        return response()->json($tax);
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tax = TaxConfiguration::where('company_id', $user->company_id)->findOrFail($id);

        // TODO: Protect system defaults - only super users can delete
        // For now, prevent deletion of system defaults
        if ($tax->is_system_default) {
            return response()->json([
                'error' => 'Cannot delete system default tax configuration. Only super users can perform this action.',
            ], 403);
        }

        if ($tax->products()->count() > 0) {
            return response()->json(['error' => 'Cannot delete tax configuration with associated products'], 400);
        }

        $tax->delete();
        return response()->json(['message' => 'Tax configuration deleted']);
    }

    public function setDefault($id)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tax = TaxConfiguration::where(function($q) use ($user) {
                $q->where('company_id', $user->company_id)
                  ->orWhereNull('company_id');
            })->findOrFail($id);

        // Disallow making a global config the company default
        if ($tax->company_id === null) {
            return response()->json([
                'error' => 'Cannot set global tax configuration as company default. Create a company-specific tax config and set that as default.'
            ], 422);
        }

        TaxConfiguration::where('company_id', $user->company_id)->update(['is_default' => false]);
        $tax->update(['is_default' => true]);

        return response()->json($tax);
    }

    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'tax_id' => 'required|exists:tax_configurations,id',
            'amount' => 'required|numeric|min:0',
            'is_inclusive' => 'boolean',
        ]);

        $tax = TaxConfiguration::findOrFail($validated['tax_id']);
        $amount = $validated['amount'];
        $inclusive = $validated['is_inclusive'] ?? null;

        return response()->json([
            'amount' => $amount,
            'tax_rate' => $tax->rate,
            'tax_amount' => round($tax->calculateTax($amount, $inclusive), 2),
            'amount_with_tax' => round($tax->calculateAmountWithTax($amount), 2),
            'amount_without_tax' => round($tax->calculateAmountWithoutTax($amount), 2),
            'is_inclusive' => $tax->is_inclusive,
        ]);
    }
}
