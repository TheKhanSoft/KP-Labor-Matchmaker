<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Support\Facades\Cache;

#[Table('settings')]
#[Fillable(['key', 'value', 'type', 'group', 'description'])]
class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    public static function get(string $key, $default = null)
    {
        try {
            $settingData = Cache::rememberForever("setting.{$key}", function () use ($key) {
                $setting = self::where('key', $key)->first();
                return $setting ? [
                    'value' => $setting->value,
                    'type' => $setting->type
                ] : null;
            });

            // If the cache contains an incomplete object (from older serialized models) or is not an array,
            // we must discard it and fetch/cache it fresh.
            if (is_object($settingData) || (is_array($settingData) === false && $settingData !== null)) {
                Cache::forget("setting.{$key}");
                $setting = self::where('key', $key)->first();
                $settingData = $setting ? [
                    'value' => $setting->value,
                    'type' => $setting->type
                ] : null;
                if ($settingData) {
                    Cache::forever("setting.{$key}", $settingData);
                }
            }

            if (!$settingData) {
                return $default;
            }

            $value = $settingData['value'];

            switch ($settingData['type']) {
                case 'boolean':
                    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                case 'integer':
                    return (int)$value;
                default:
                    return $value;
            }
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Calculate credit price based on configured settings policy.
     */
    public static function calculateCreditPrice(int $credits): int
    {
        $credits = max(1, $credits);
        $mode = self::get('credit_pricing_mode', 'flat');
        $flatRate = (int)self::get('credit_flat_rate', 20);

        $tiersJson = self::get('credit_pricing_tiers', '[]');
        $tiers = json_decode($tiersJson, true) ?: [];

        if ($mode === 'flat' || empty($tiers)) {
            return $credits * $flatRate;
        }

        if ($mode === 'tiered') {
            // Bulk pricing (monotonic to prevent price drop for higher quantities)
            return self::calculateMonotonicBulkPrice($credits, $flatRate, $tiers);
        }

        if ($mode === 'cumulative') {
            // Cumulative bracketed/graduated pricing
            return self::calculateCumulativePrice($credits, $flatRate, $tiers);
        }

        return $credits * $flatRate;
    }

    /**
     * Helper to calculate raw bulk price for a specific quantity
     */
    public static function calculateRawBulkPrice(int $credits, int $flatRate, array $tiers): int
    {
        if ($credits <= 0) {
            return 0;
        }
        
        // Sort tiers by min descending to find the highest matching tier
        usort($tiers, function ($a, $b) {
            return (int)$b['min'] <=> (int)$a['min'];
        });

        foreach ($tiers as $tier) {
            if ($credits >= (int)$tier['min']) {
                return $credits * (int)$tier['price'];
            }
        }

        return $credits * $flatRate;
    }

    /**
     * Helper to calculate bulk price ensuring monotonic cost growth
     */
    public static function calculateMonotonicBulkPrice(int $credits, int $flatRate, array $tiers): int
    {
        $maxPrice = self::calculateRawBulkPrice($credits, $flatRate, $tiers);
        
        // Sort tiers ascending by min
        usort($tiers, function ($a, $b) {
            return (int)$a['min'] <=> (int)$b['min'];
        });
        
        foreach ($tiers as $tier) {
            $tierMin = (int)$tier['min'];
            if ($tierMin - 1 < $credits) {
                $rawPriceAtBoundary = self::calculateRawBulkPrice($tierMin - 1, $flatRate, $tiers);
                if ($rawPriceAtBoundary > $maxPrice) {
                    $maxPrice = $rawPriceAtBoundary;
                }
            }
        }
        
        return $maxPrice;
    }

    /**
     * Helper to calculate cumulative bracketed price
     */
    public static function calculateCumulativePrice(int $credits, int $flatRate, array $tiers): int
    {
        // Sort tiers ascending by min
        usort($tiers, function ($a, $b) {
            return (int)$a['min'] <=> (int)$b['min'];
        });
        
        $total = 0;
        $prevMin = 1;
        $prevPrice = $flatRate;
        
        foreach ($tiers as $tier) {
            $tierMin = (int)$tier['min'];
            $tierPrice = (int)$tier['price'];
            
            if ($credits >= $tierMin) {
                $unitsInBracket = $tierMin - $prevMin;
                $total += $unitsInBracket * $prevPrice;
                $prevMin = $tierMin;
                $prevPrice = $tierPrice;
            } else {
                break;
            }
        }
        
        $remainingUnits = $credits - $prevMin + 1;
        if ($remainingUnits > 0) {
            $total += $remainingUnits * $prevPrice;
        }
        
        return $total;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, $value): bool
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) {
            return false;
        }

        if ($setting->type === 'boolean') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        } else {
            $value = (string)$value;
        }

        $setting->value = $value;
        $setting->save();

        Cache::forget("setting.{$key}");

        return true;
    }
}
