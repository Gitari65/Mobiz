<?php

namespace App\Services;

use App\Models\PriceGroup;
use Illuminate\Support\Facades\Schema;

class PriceGroupService
{
    public const DEFAULT_GROUPS = [
        [
            'name' => 'Retail Default',
            'code' => 'RETAIL_DEFAULT',
            'description' => 'Standard walk-in customer pricing',
            'discount_percentage' => 0,
        ],
        [
            'name' => 'Stockist',
            'code' => 'STOCKIST',
            'description' => 'Preferred reseller and stockist pricing tier',
            'discount_percentage' => 5,
        ],
        [
            'name' => 'Wholesale',
            'code' => 'WHOLESALE',
            'description' => 'Bulk buyer pricing tier',
            'discount_percentage' => 10,
        ],
    ];

    public static function ensureDefaultsForCompany(?int $companyId): void
    {
        if (!$companyId) {
            return;
        }

        foreach (self::DEFAULT_GROUPS as $group) {
            $priceGroup = PriceGroup::firstOrNew([
                'company_id' => $companyId,
                'code' => $group['code'],
            ]);

            $priceGroup->name = $group['name'];
            $priceGroup->description = $group['description'];
            $priceGroup->discount_percentage = $group['discount_percentage'];
            $priceGroup->is_system = true;

            if (self::hasIsEnabledColumn()) {
                if (!$priceGroup->exists) {
                    $priceGroup->is_enabled = true;
                }

                if (self::isRetailDefault($priceGroup)) {
                    $priceGroup->is_enabled = true;
                }
            }

            $priceGroup->save();
        }
    }

    public static function hasIsEnabledColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('price_groups', 'is_enabled');
        }

        return $hasColumn;
    }

    public static function isRetailDefault(?PriceGroup $priceGroup): bool
    {
        if (!$priceGroup) {
            return true;
        }

        $normalizedCode = self::normalizeIdentifier($priceGroup->code);
        $normalizedName = self::normalizeIdentifier($priceGroup->name);

        return in_array($normalizedCode, ['retail_default', 'retail'], true)
            || in_array($normalizedName, ['retail_default', 'retail'], true);
    }

    public static function normalizeIdentifier(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';

        return trim($value, '_');
    }
}