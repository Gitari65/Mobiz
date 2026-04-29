<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PriceGroup;
use App\Services\PriceGroupService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PriceGroupController extends Controller
{
    /**
     * Display a listing of price groups.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        PriceGroupService::ensureDefaultsForCompany($user->company_id);

        $includeDisabled = $request->boolean('include_disabled', false);
        $hasIsEnabledColumn = PriceGroupService::hasIsEnabledColumn();

        $priceGroups = PriceGroup::where('company_id', $user->company_id)
        ->when($hasIsEnabledColumn && !$includeDisabled, function ($query) {
            $query->where('is_enabled', true);
        })
        ->orderBy('is_system', 'desc')
        ->orderBy('name')
        ->get();

        return response()->json($priceGroups);
    }

    /**
     * Store a newly created price group.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        PriceGroupService::ensureDefaultsForCompany($user->company_id);
        $hasIsEnabledColumn = PriceGroupService::hasIsEnabledColumn();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('price_groups', 'code')->where(fn ($query) => $query->where('company_id', $user->company_id)),
            ],
            'description' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'is_enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $priceGroup = PriceGroup::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'discount_percentage' => $request->discount_percentage,
            'is_system' => false,
            'company_id' => $user->company_id
        ]);

        if ($hasIsEnabledColumn) {
            $priceGroup->update([
                'is_enabled' => $request->boolean('is_enabled', true),
            ]);
        }

        return response()->json([
            'message' => 'Price group created successfully',
            'data' => $priceGroup
        ], 201);
    }

    /**
     * Display the specified price group.
     */
    public function show($id)
    {
        $user = request()->user();
        $priceGroup = PriceGroup::where('company_id', $user->company_id)
            ->with('customers')
            ->findOrFail($id);
        return response()->json($priceGroup);
    }

    /**
     * Update the specified price group.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        PriceGroupService::ensureDefaultsForCompany($user->company_id);
        $hasIsEnabledColumn = PriceGroupService::hasIsEnabledColumn();
        $priceGroup = PriceGroup::where('company_id', $user->company_id)->findOrFail($id);

        $nextCode = strtoupper((string) $request->code);

        if ($priceGroup->is_system && $priceGroup->code !== $nextCode) {
            return response()->json([
                'message' => 'Default pricing group codes cannot be changed'
            ], 422);
        }

        if (
            $hasIsEnabledColumn
            && PriceGroupService::isRetailDefault($priceGroup)
            && $request->has('is_enabled')
            && !$request->boolean('is_enabled')
        ) {
            return response()->json([
                'message' => 'Retail Default pricing group must always remain enabled'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('price_groups', 'code')
                    ->ignore($id)
                    ->where(fn ($query) => $query->where('company_id', $user->company_id)),
            ],
            'description' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'is_enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = [
            'name' => $request->name,
            'code' => $nextCode,
            'description' => $request->description,
            'discount_percentage' => $request->discount_percentage,
        ];

        if ($hasIsEnabledColumn) {
            $updateData['is_enabled'] = $request->boolean('is_enabled', (bool) $priceGroup->is_enabled);
        }

        $priceGroup->update($updateData);

        return response()->json([
            'message' => 'Price group updated successfully',
            'data' => $priceGroup
        ]);
    }

    /**
     * Remove the specified price group.
     */
    public function destroy($id)
    {
        $user = request()->user();
        $priceGroup = PriceGroup::where('company_id', $user->company_id)->findOrFail($id);

        if ($priceGroup->is_system) {
            return response()->json([
                'message' => 'Default pricing groups cannot be deleted. Disable them instead.'
            ], 403);
        }

        // Check if price group has customers
        if ($priceGroup->customers()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete price group with assigned customers'
            ], 422);
        }

        $priceGroup->delete();

        return response()->json([
            'message' => 'Price group deleted successfully'
        ]);
    }

    public function toggle(Request $request, $id)
    {
        $user = $request->user();
        PriceGroupService::ensureDefaultsForCompany($user->company_id);

        if (!PriceGroupService::hasIsEnabledColumn()) {
            return response()->json([
                'message' => 'Pricing group toggles require the latest database migration. Please run migrations and try again.'
            ], 422);
        }

        $priceGroup = PriceGroup::where('company_id', $user->company_id)->findOrFail($id);

        if (PriceGroupService::isRetailDefault($priceGroup) && $priceGroup->is_enabled) {
            return response()->json([
                'message' => 'Retail Default pricing group must always remain enabled'
            ], 422);
        }

        $priceGroup->update([
            'is_enabled' => !$priceGroup->is_enabled,
        ]);

        return response()->json([
            'message' => $priceGroup->is_enabled ? 'Pricing group enabled successfully' : 'Pricing group disabled successfully',
            'data' => $priceGroup,
        ]);
    }
}
