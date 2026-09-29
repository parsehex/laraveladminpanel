<?php

namespace App\Imports;

class TruckApplianceCsvImportResult
{
    public function __construct(
        public readonly int $imported,
        public readonly int $updated,
    ) {}
}
