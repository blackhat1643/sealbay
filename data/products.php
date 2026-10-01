<?php
/**
 * SAMPLE PRODUCTS — placeholders so the shop can be demonstrated.
 *
 * Sizes, prices, stock levels, temperature ranges and fitment notes below are
 * illustrative only. Replace them with your real range in Admin → Products
 * (or edit this file before running the installer) and confirm every rating
 * against your supplier's data sheet before launch.
 *
 * Prices are AUD cents, GST inclusive.
 */
defined('ST_APP') || exit;

$temps = [
    'NBR'                     => '-30 °C to +100 °C',
    'FKM'                     => '-20 °C to +200 °C',
    'Polyurethane'            => '-30 °C to +100 °C',
    'PTFE with NBR energiser' => '-30 °C to +100 °C',
];
$size = static fn (float $v): string => rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
$key  = static fn (float $v): string => str_replace('.', 'p', $size($v));

$products = [];

/* ---- Rotary shaft seals (TC double-lip with spring) ---- */
$rotary = [
    // id, od, w, material, price, stock, featured
    [20, 35, 7, 'NBR', 650, 64, false], [20, 47, 7, 'NBR', 720, 38, false], [25, 40, 7, 'NBR', 690, 80, true],
    [25, 47, 7, 'NBR', 740, 52, false], [25, 52, 7, 'NBR', 790, 4, false], [30, 47, 7, 'NBR', 760, 71, false],
    [30, 52, 7, 'NBR', 820, 45, false], [30, 62, 7, 'NBR', 940, 0, false], [35, 52, 7, 'NBR', 890, 96, true],
    [35, 62, 7, 'NBR', 980, 33, false], [35, 72, 10, 'NBR', 1190, 12, false], [40, 62, 7, 'NBR', 1040, 58, true],
    [40, 72, 10, 'NBR', 1260, 3, false], [45, 62, 8, 'NBR', 1120, 27, false], [45, 72, 8, 'NBR', 1240, 19, false],
    [50, 72, 8, 'NBR', 1290, 41, true], [55, 80, 8, 'NBR', 1450, 14, false], [60, 80, 8, 'NBR', 1520, 22, false],
    [25, 40, 7, 'FKM', 1690, 18, false], [30, 47, 7, 'FKM', 1850, 9, false], [35, 52, 7, 'FKM', 2150, 24, false],
    [40, 62, 7, 'FKM', 2480, 0, false], [50, 72, 8, 'FKM', 2990, 7, false],
];
foreach ($rotary as [$id, $od, $w, $material, $price, $stock, $featured]) {
    $dims = $size($id) . 'x' . $size($od) . 'x' . $size($w);
    $products[] = [
        'category'       => 'rotary-shaft-seals',
        'slug'           => 'tc-' . $key($id) . 'x' . $key($od) . 'x' . $key($w) . '-rotary-shaft-seal-' . strtolower($material),
        'sku'            => 'TC-' . $key($id) . '-' . $key($od) . '-' . $key($w) . '-' . $material,
        'name'           => 'TC ' . $dims . ' Rotary Shaft Seal' . ($material === 'NBR' ? '' : ' (' . $material . ')'),
        'seal_type'      => 'rotary',
        'style'          => 'TC double-lip with spring',
        'material'       => $material,
        'inner_diameter' => $id, 'outer_diameter' => $od, 'width' => $w,
        'temp_range'     => $temps[$material],
        'price_cents'    => $price, 'stock_qty' => $stock, 'allow_backorder' => true,
        'summary'        => 'Double-lip rotary shaft seal with garter spring. Rubber-covered outside, steel insert, a spring-loaded main lip and a second dust lip.'
            . ($material === 'FKM' ? ' FKM (often sold as Viton™) for hotter running and wider fluid resistance.' : ' NBR for everyday mineral oil and grease.'),
        'fitment'        => $id <= 30
            ? 'A common size on small gearboxes, electric motors and pumps. Check the three numbers moulded on your old seal before ordering.'
            : 'A common size on gearboxes, PTO shafts and wheel hubs. Check the three numbers moulded on your old seal before ordering.',
        'illustration'   => 'tc',
        'is_featured'    => $featured,
    ];
}

/* ---- Hydraulic rod seals ---- */
foreach ([[25, 35, 6, 990, 40], [30, 40, 6, 1090, 36], [35, 45, 6, 1190, 5], [40, 50, 7, 1340, 28], [45, 55, 7, 1450, 0], [50, 60, 7, 1590, 17], [60, 70, 7, 1790, 11]] as [$id, $od, $w, $price, $stock]) {
    $dims = $size($id) . 'x' . $size($od) . 'x' . $size($w);
    $products[] = [
        'category'       => 'hydraulic-seals',
        'slug'           => 'rod-seal-' . $key($id) . 'x' . $key($od) . 'x' . $key($w) . '-polyurethane',
        'sku'            => 'RS-' . $key($id) . '-' . $key($od) . '-' . $key($w) . '-PU',
        'name'           => 'Hydraulic Rod Seal ' . $dims,
        'seal_type'      => 'rod',
        'style'          => 'U-cup, single-acting',
        'material'       => 'Polyurethane',
        'inner_diameter' => $id, 'outer_diameter' => $od, 'width' => $w,
        'temp_range'     => $temps['Polyurethane'],
        'price_cents'    => $price, 'stock_qty' => $stock, 'allow_backorder' => true,
        'summary'        => 'U-cup rod seal for hydraulic cylinders. Fits in the gland and seals on the rod as it slides in and out. The open side of the U faces the oil.',
        'fitment'        => 'For cylinders with a ' . $size($id) . ' mm rod. Inner diameter is the rod; outer diameter is the groove in the gland.',
        'illustration'   => 'rod-seal',
        'is_featured'    => $id === 40,
    ];
}

/* ---- Hydraulic piston seals ---- */
foreach ([[29, 40, 4.2, 1650, 22], [39, 50, 4.2, 1890, 30], [47.5, 63, 6.3, 2390, 13], [64.5, 80, 6.3, 2890, 6], [84.5, 100, 6.3, 3490, 0]] as [$id, $od, $w, $price, $stock]) {
    $dims = $size($id) . 'x' . $size($od) . 'x' . $size($w);
    $products[] = [
        'category'       => 'hydraulic-seals',
        'slug'           => 'piston-seal-' . $key($id) . 'x' . $key($od) . 'x' . $key($w) . '-ptfe',
        'sku'            => 'PS-' . $key($id) . '-' . $key($od) . '-' . $key($w),
        'name'           => 'Hydraulic Piston Seal ' . $dims,
        'seal_type'      => 'piston',
        'style'          => 'Double-acting, slide ring with O-ring',
        'material'       => 'PTFE with NBR energiser',
        'inner_diameter' => $id, 'outer_diameter' => $od, 'width' => $w,
        'temp_range'     => $temps['PTFE with NBR energiser'],
        'price_cents'    => $price, 'stock_qty' => $stock, 'allow_backorder' => true,
        'summary'        => 'Two-piece piston seal: a low-friction PTFE ring pressed against the bore by a rubber O-ring underneath. Seals in both directions.',
        'fitment'        => 'For cylinders with a ' . $size($od) . ' mm bore. Outer diameter is the bore; inner diameter is the bottom of the groove on the piston.',
        'illustration'   => 'piston-seal',
        'is_featured'    => $od === 50,
    ];
}

/* ---- Wipers ---- */
foreach ([[25, 33, 5, 690, 44], [30, 38, 5, 740, 51], [35, 43, 5, 790, 2], [40, 48, 5, 850, 37], [50, 58, 5, 950, 26], [60, 68, 5, 1090, 0]] as [$id, $od, $w, $price, $stock]) {
    $dims = $size($id) . 'x' . $size($od) . 'x' . $size($w);
    $products[] = [
        'category'       => 'hydraulic-seals',
        'slug'           => 'wiper-' . $key($id) . 'x' . $key($od) . 'x' . $key($w) . '-polyurethane',
        'sku'            => 'WP-' . $key($id) . '-' . $key($od) . '-' . $key($w) . '-PU',
        'name'           => 'Rod Wiper ' . $dims,
        'seal_type'      => 'wiper',
        'style'          => 'Snap-in, single lip',
        'material'       => 'Polyurethane',
        'inner_diameter' => $id, 'outer_diameter' => $od, 'width' => $w,
        'temp_range'     => $temps['Polyurethane'],
        'price_cents'    => $price, 'stock_qty' => $stock, 'allow_backorder' => true,
        'summary'        => 'Snap-in wiper for the outer end of the gland. Scrapes dirt and water off the rod so it is not dragged into the cylinder.',
        'fitment'        => 'For cylinders with a ' . $size($id) . ' mm rod. Replace the wiper whenever you replace the rod seal.',
        'illustration'   => 'wiper',
        'is_featured'    => false,
    ];
}

/* ---- Seal kits ---- */
foreach ([[40, 25, 3890, 9], [50, 30, 4490, 15], [63, 35, 5490, 4], [80, 45, 6890, 0]] as [$bore, $rod, $price, $stock]) {
    $products[] = [
        'category'       => 'seal-kits',
        'slug'           => 'cylinder-seal-kit-' . $bore . 'mm-bore-' . $rod . 'mm-rod',
        'sku'            => 'KIT-' . $bore . '-' . $rod,
        'name'           => 'Cylinder Seal Kit — ' . $bore . ' mm bore, ' . $rod . ' mm rod',
        'seal_type'      => 'kit',
        'style'          => 'Double-acting cylinder',
        'material'       => 'Mixed',
        'inner_diameter' => null, 'outer_diameter' => null, 'width' => null,
        'temp_range'     => '-30 °C to +100 °C',
        'price_cents'    => $price, 'stock_qty' => $stock, 'allow_backorder' => true,
        'summary'        => 'Everything needed to reseal one double-acting cylinder with a ' . $bore . ' mm bore and ' . $rod . ' mm rod.',
        'fitment'        => 'Check both the bore and the rod diameter of your cylinder. Groove sizes vary between makers — if you are not sure, send us a photo of the old seals.',
        'kit_contents'   => ['1 × piston seal', '1 × rod seal', '1 × rod wiper', '2 × static O-rings'],
        'illustration'   => 'seal-kit',
        'is_featured'    => $bore === 50,
    ];
}

return $products;
