<?php

namespace App\Support;

use App\Models\Truck;

class TruckPricing
{
    /**
     * Spread the truck cost across its units by MSRP share, or evenly when MSRP totals 0.
     */
    public static function recalculate(Truck $truck): void
    {
        $items = $truck->appliances()->get(['id', 'msrp']);
        $totalMsrp = (float) $items->sum('msrp');

        if ($totalMsrp > 0) {
            $percentage = (float) $truck->cost_of_truck / $totalMsrp;

            foreach ($items as $item) {
                $item->update(['price' => $percentage * (float) $item->msrp]);
            }

            return;
        }

        $count = $items->count();
        if ($count > 0) {
            $price = (float) $truck->cost_of_truck / $count;
            foreach ($items as $item) {
                $item->update(['price' => $price]);
            }
        }
    }
}
