<?php
/**
 * Materials guide (draft copy). Shown on /materials.php and referenced on product pages.
 *
 * The temperature ranges are TYPICAL published figures for each material family and
 * are placeholders — confirm them against your supplier's data sheets, and keep the
 * figure on each product page as the one that applies to that seal.
 */
defined('ST_APP') || exit;

return [
    [
        'code'       => 'NBR',
        'slug'       => 'nbr',
        'name'       => 'Nitrile rubber',
        'aka'        => 'Also called nitrile or Buna-N. Usually black.',
        'summary'    => 'The everyday choice for mineral oil and grease.',
        'temp_range' => '-30 °C to +100 °C',
        'good_for'   => ['Gearbox oil, engine oil and grease', 'Hydraulic oil', 'General farm and workshop machinery'],
        'watch_out'  => ['Hardens and cracks if it runs too hot for too long', 'Not suited to brake fluid or strong solvents', 'Ages in sunlight and ozone, so store spares in the dark'],
        'choose_if'  => 'Your old seal was plain black rubber and the machine runs at normal oil temperatures.',
    ],
    [
        'code'       => 'FKM',
        'slug'       => 'fkm',
        'name'       => 'Fluoroelastomer',
        'aka'        => 'Often sold under the trade name Viton™. Often brown, sometimes black or green.',
        'summary'    => 'Handles more heat and more chemicals than NBR, and costs more.',
        'temp_range' => '-20 °C to +200 °C',
        'good_for'   => ['Hot-running gearboxes, engines and pumps', 'Synthetic oils and fuels', 'Seals that sit close to an exhaust or other heat'],
        'watch_out'  => ['Stiffer than NBR in very cold weather', 'Not an upgrade for every fluid — check before using with brake fluid or steam', 'Costs noticeably more than NBR'],
        'choose_if'  => 'The NBR seal you took out was hard, cracked or glazed from heat, or the maker specifies FKM.',
    ],
    [
        'code'       => 'PU',
        'slug'       => 'polyurethane',
        'name'       => 'Polyurethane',
        'aka'        => 'Also written PU or AU. Often blue, green, red or translucent.',
        'summary'    => 'Tough and wear-resistant — the usual material for hydraulic rod seals and wipers.',
        'temp_range' => '-30 °C to +100 °C',
        'good_for'   => ['Hydraulic rod seals and wipers', 'Cylinders that see dirt, shock loads and side loads', 'Mineral hydraulic oil'],
        'watch_out'  => ['Breaks down in hot water and steam', 'Check compatibility with water-based or fire-resistant fluids', 'Stiffer to fit than rubber — warm it in clean oil rather than forcing it'],
        'choose_if'  => 'You are resealing a hydraulic cylinder and the old seal was a firm, coloured plastic-like ring.',
    ],
    [
        'code'       => 'PTFE',
        'slug'       => 'ptfe',
        'name'       => 'PTFE',
        'aka'        => 'A hard, slippery plastic. Usually white, bronze-coloured or dark grey when filled.',
        'summary'    => 'Very low friction. Used as the sliding face of piston seals, backed by a rubber ring.',
        'temp_range' => 'Set by the rubber ring behind it — see the product page',
        'good_for'   => ['Piston seals that must slide smoothly without sticking', 'Guide and wear rings', 'Cylinders that sit loaded for long periods'],
        'watch_out'  => ['Not stretchy — follow the fitting notes so it is not kinked', 'Needs a rubber energiser behind it to seal', 'Scratches easily on sharp groove edges'],
        'choose_if'  => 'The old piston seal was a thin hard ring sitting on top of an O-ring.',
    ],
];
