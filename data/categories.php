<?php
/**
 * Default shop categories. Managed in Admin → Categories once the database is installed.
 */
defined('ST_APP') || exit;

return [
    [
        'slug'         => 'rotary-shaft-seals',
        'name'         => 'Rotary Shaft Seals',
        'headline'     => 'Rotary shaft seals by size',
        'summary'      => 'Oil seals for rotating shafts in gearboxes, pumps, motors, wheel hubs and PTO shafts.',
        'description'  => "Rotary shaft seals — most people call them oil seals — keep oil or grease in and dirt out where a turning shaft passes through a housing. You will find them on gearboxes, pumps, electric motors, wheel hubs and PTO shafts.\n\nThe style we stock most is the TC: a rubber-covered seal with a steel insert, a spring-loaded main lip and a second dust lip. Sizes are listed as inner diameter × outer diameter × width in millimetres, the same three numbers usually moulded on the old seal.",
        'illustration' => 'tc',
    ],
    [
        'slug'         => 'hydraulic-seals',
        'name'         => 'Hydraulic Seals',
        'headline'     => 'Hydraulic rod seals, piston seals and wipers',
        'summary'      => 'Rod seals, piston seals and wipers for hydraulic cylinders and rams.',
        'description'  => "A hydraulic cylinder uses a few different seals, each with its own job. Rod seals seal the rod as it slides in and out of the cylinder. Piston seals seal the piston inside the cylinder bore. Wipers keep dirt from being dragged into the cylinder as the rod retracts.\n\nMeasure the old seal or the groove it sits in, then use the size finder. If you are rebuilding a whole cylinder, a seal kit covers every seal in one go.",
        'illustration' => 'rod-seal',
    ],
    [
        'slug'         => 'seal-kits',
        'name'         => 'Seal Kits',
        'headline'     => 'Seal kits for rams and cylinders',
        'summary'      => 'Repair kits with the rod seal, piston seal, wiper and static seals for one cylinder.',
        'description'  => "A seal kit puts every seal for one cylinder in a single bag, so a rebuild does not stall on a missing part. Kits are listed by cylinder bore and rod diameter.\n\nCannot see your cylinder size? Send us a photo of the old seals with a ruler beside them and we will quote a kit to suit.",
        'illustration' => 'seal-kit',
    ],
];
