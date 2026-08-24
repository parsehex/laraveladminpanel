<?php

// Seed source only (php artisan deman-flows:import / FlowSeeder).
// Runtime loads deman prompts from the deman_flows / deman_flow_prompts tables.

return [
    'refrigerators' => [
        'compressor' => 'Compressor system (e.g., compressor, condenser coils)',
        'doors' => 'Doors and seals (e.g., handles, gaskets)',
        'shelves' => 'Interior shelves and drawers',
        'electronics' => 'Control boards and sensors',
        'evaporator' => 'Evaporator fan and coils',
    ],
    'dryers' => [
        'heating' => 'Heating element or gas valve',
        'drum' => 'Drum and belt',
        'motor' => 'Motor and blower',
        'controls' => 'Control board and timer',
        'vents' => 'Vents and lint filter',
    ],
    'washers' => [
        'drum' => 'Drum and tub assembly',
        'pump' => 'Water pump and hoses',
        'motor' => 'Motor and transmission',
        'controls' => 'Control board and switches',
        'agitator' => 'Agitator or impeller',
    ],
    'ranges' => [
        'burners' => 'Burners or elements',
        'oven' => 'Oven door and hinges',
        'controls' => 'Control board and knobs',
        'igniters' => 'Igniters and sensors',
        'racks' => 'Oven racks',
    ],
    'microwave' => [
        'magnetron' => 'Magnetron',
        'turntable' => 'Turntable and motor',
        'controls' => 'Control panel',
        'door' => 'Door switches and latches',
        'transformer' => 'High-voltage transformer',
    ],
];
