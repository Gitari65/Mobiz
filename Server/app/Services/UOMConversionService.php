<?php

namespace App\Services;

use App\Models\UOM;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UOMConversionService
{
    /**
     * Convert a quantity from one UOM to another
     * 
     * @param float $quantity The quantity to convert
     * @param int|string $fromUomId The source UOM ID or abbreviation
     * @param int|string $toUomId The target UOM ID or abbreviation
     * @return float The converted quantity
     */
    public static function convert(float $quantity, $fromUomId, $toUomId)
    {
        if ($quantity == 0) {
            return 0;
        }

        // Get UOM IDs if abbreviations provided
        if (is_string($fromUomId)) {
            $fromUomId = self::getUomId($fromUomId);
        }
        if (is_string($toUomId)) {
            $toUomId = self::getUomId($toUomId);
        }

        // If same UOM, return same quantity
        if ($fromUomId === $toUomId) {
            return $quantity;
        }

        $factor = self::getConversionFactor($fromUomId, $toUomId);
        return $quantity * $factor;
    }

    /**
     * Get conversion factor between two UOMs
     * Uses cache for performance
     */
    public static function getConversionFactor(int $fromUomId, int $toUomId): float
    {
        return self::resolveConversionFactor($fromUomId, $toUomId) ?? 1.0;
    }

    /**
     * Resolve conversion factor between two UOMs.
     * Returns null when no direct or reverse conversion exists.
     */
    public static function resolveConversionFactor(?int $fromUomId, ?int $toUomId): ?float
    {
        if (!$fromUomId || !$toUomId) {
            return null;
        }

        if ($fromUomId === $toUomId) {
            return 1.0;
        }

        $cacheKey = "uom_conversion_nullable_{$fromUomId}_{$toUomId}";

        return Cache::remember($cacheKey, 3600, function () use ($fromUomId, $toUomId) {
            $conversion = DB::table('uom_conversions')
                ->where('from_uom_id', $fromUomId)
                ->where('to_uom_id', $toUomId)
                ->first();

            if ($conversion) {
                return (float) $conversion->conversion_factor;
            }

            $reverseConversion = DB::table('uom_conversions')
                ->where('from_uom_id', $toUomId)
                ->where('to_uom_id', $fromUomId)
                ->first();

            if ($reverseConversion) {
                return 1.0 / (float) $reverseConversion->conversion_factor;
            }

            return null;
        });
    }

    /**
     * Get UOM ID by abbreviation
     */
    private static function getUomId(string $abbreviation): int
    {
        $uom = UOM::where('abbreviation', $abbreviation)->first();
        return $uom ? $uom->id : 0;
    }

    /**
     * Get all UOMs of the same type
     */
    public static function getUomsOfType(string $type): array
    {
        return UOM::where('type', $type)->pluck('id')->toArray();
    }

    /**
     * Get smallest UOM in a type (for stock calculation)
     * E.g., ml for volume, g for weight, mm for length
     */
    public static function getSmallestUomInType(string $type): ?UOM
    {
        $smallestMap = [
            'volume' => 'ml',
            'weight' => 'mg',
            'length' => 'mm',
            'area' => 'cm²',
            'count' => 'pcs',
        ];

        $abbreviation = $smallestMap[$type] ?? null;
        if (!$abbreviation) {
            return null;
        }

        return UOM::where('abbreviation', $abbreviation)->first();
    }

    /**
     * Get largest UOM in a type (for purchase)
     * E.g., L for volume, kg for weight, m for length
     */
    public static function getLargestUomInType(string $type): ?UOM
    {
        $largestMap = [
            'volume' => 'L',
            'weight' => 'kg',
            'length' => 'm',
            'area' => 'm²',
            'count' => 'ctn',
        ];

        $abbreviation = $largestMap[$type] ?? null;
        if (!$abbreviation) {
            return null;
        }

        return UOM::where('abbreviation', $abbreviation)->first();
    }

    /**
     * Convert quantity from purchase UOM to smallest unit for inventory
     * E.g., 10 kg → 10000 g
     */
    public static function convertToSmallestUnit(float $quantity, string $purchaseUomId): array
    {
        if ($quantity == 0) {
            return ['quantity' => 0, 'uom_id' => null, 'uom' => null];
        }

        $purchaseUom = UOM::find($purchaseUomId);
        if (!$purchaseUom) {
            return ['quantity' => $quantity, 'uom_id' => $purchaseUomId, 'uom' => null];
        }

        $smallestUom = self::getSmallestUomInType($purchaseUom->type);
        if (!$smallestUom) {
            return ['quantity' => $quantity, 'uom_id' => $purchaseUomId, 'uom' => $purchaseUom];
        }

        $convertedQuantity = self::convert($quantity, $purchaseUomId, $smallestUom->id);

        return [
            'quantity' => $convertedQuantity,
            'uom_id' => $smallestUom->id,
            'uom' => $smallestUom,
        ];
    }

    /**
     * Convert inventory quantity to all sale UOM options
     * E.g., 10000g → [1000 L, 2000 500ml, 13 750ml, etc.]
     */
    public static function convertToSaleUoms(float $inventoryQuantity, int $inventoryUomId, array $saleUomIds): array
    {
        $results = [];

        foreach ($saleUomIds as $saleUomId) {
            $convertedQuantity = self::convert($inventoryQuantity, $inventoryUomId, $saleUomId);
            $results[$saleUomId] = $convertedQuantity;
        }

        return $results;
    }

    /**
     * Calculate total stock in smallest unit
     * Useful for inventory aggregation
     */
    public static function getTotalStockInSmallestUnit(int $productId): array
    {
        $product = \App\Models\Product::find($productId);
        if (!$product) {
            return ['total' => 0, 'uom' => null];
        }

        if (!$product->purchase_uom_id) {
            return ['total' => 0, 'uom' => null];
        }

        $purchaseUom = UOM::find($product->purchase_uom_id);
        if (!$purchaseUom) {
            return ['total' => 0, 'uom' => null];
        }

        $smallestUom = self::getSmallestUomInType($purchaseUom->type);
        if (!$smallestUom) {
            return ['total' => $product->quantity, 'uom' => $purchaseUom];
        }

        $totalInSmallest = self::convert($product->quantity, $product->purchase_uom_id, $smallestUom->id);

        return [
            'total' => $totalInSmallest,
            'uom' => $smallestUom,
        ];
    }

    /**
     * Clear conversion cache (call after adding new conversions)
     */
    public static function clearConversionCache(): void
    {
        Cache::flush();
    }
}
