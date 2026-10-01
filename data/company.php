<?php
/**
 * The company behind the shop — content from the Sealing Technologies corporate profile.
 * Shown on /about.php and in the "Backed by" band on the homepage.
 *
 * Client names and brand partnerships below were confirmed by the business owner.
 * Client LOGOS are only shown when you place a file you have permission to use in
 * assets/img/clients/<slug>.svg (or .png / .webp); until then the name is shown as text.
 */
defined('ST_APP') || exit;

return [
    'name'     => 'Sealing Technologies',
    'location' => 'Vadodara, Gujarat, India',
    'founded'  => '2002',

    'intro'   => 'Sealing Technologies was founded in 2002 and specialises in the trading and distribution of high-performance industrial seals. We connect sealing technology from leading global manufacturers with what plant and machinery owners need on the ground, backed by deep technical knowledge of how seals are applied.',
    'mission' => 'Our mission is to reduce operational downtime and fluid leakage in heavy industry by supplying authentic, high-integrity sealing components. As a specialised trading company we put quality, authenticity and fast technical support first.',

    // [value, label, note]
    'facts' => [
        ['2002', 'Founded', 'in Vadodara, Gujarat'],
        ['25+', 'Years of field experience', 'in industrial sealing'],
        ['16', 'Sales engineers', 'technically trained field team'],
        ['10–1000 mm', 'Outside diameters stocked', 'metric and imperial sizes'],
    ],

    'principal' => [
        'name'    => 'Parker Hannifin',
        'country' => 'USA',
        'status'  => 'National channel partner for Parker Hannifin in India',
        'text'    => 'Sealing Technologies is a national channel partner for Parker Hannifin (USA), a global leader in motion and control technologies.',
        'points'  => [
            ['Genuine components', 'Parker products supplied through the authorised channel.'],
            ['Technical support', 'Backed by the manufacturer’s own engineering support.'],
            ['Standardised designs', 'Seal designs that follow international standards.'],
        ],
    ],
    'brands' => [
        ['JM Clipper', 'USA'],
        ['Stuwe', 'Germany'],
        ['GLUAL', 'Spain'],
    ],

    // [title, text, link path or null]
    'supply' => [
        ['Rotary shaft seals', 'Rotary seals designed to keep friction, heat and wear low in rotating machinery: single and double-lip industrial oil seals, including hydrodynamic wave-lip designs, in FKM (Viton™), PTFE, NBR and EPDM, with options for high shaft speeds and chemical media.', '/shop/rotary-shaft-seals'],
        ['Hydraulic cylinder seal kits', 'Complete cylinder seal kits, configured as one sealing system to prevent leakage and protect cylinders in abrasive conditions.', '/shop/seal-kits'],
        ['Rod seals', 'Hold operating pressure inside the cylinder and stop fluid leaking out along the rod.', '/shop/hydraulic-seals'],
        ['Piston seals', 'Keep pressure separated between the two sides of the piston under heavy loads in both directions.', '/shop/hydraulic-seals'],
        ['Wiper seals', 'The first barrier: they clean the rod on every stroke, keeping out mud, dust and moisture so the rod is not scored.', '/shop/hydraulic-seals'],
        ['Guide rings and wear bands', 'Carry radial and side loads and prevent metal-to-metal contact.', '/custom-quote.php'],
        ['High-pressure O-rings', 'Close-tolerance static seals for stationary machinery joints.', '/custom-quote.php'],
        ['Back-up rings', 'Support elastomer seals against extrusion at high fluid pressure.', '/custom-quote.php'],
        ['Custom maintenance packs', 'Bundles put together around legacy equipment dimensions or your own list.', '/custom-quote.php'],
    ],

    'industries' => ['Cement and steel mills', 'Oil and gas pipelines', 'Fertiliser reactors', 'Paper and pulp mills', 'CNC machine tools', 'Heavy gearbox OEMs'],

    // [title, heading, text]
    'sectors' => [
        ['Cement', 'Preventing leakage in dusty environments', 'Cement mills run in abrasive, dust-laden air. Sealing systems built around Parker components protect bearings and gearboxes from early failure and help keep the plant running.'],
        ['Fertilisers and chemicals', 'Corrosion and high-temperature sealing', 'Chemical-resistant PTFE and FKM (Viton™) seals for aggressive fertiliser synthesis media, high-temperature zones and moving pipeline joints.'],
        ['Gearbox and machine tool OEMs', 'High shaft speeds and high torque', 'Rotary shaft seals for heavy gearbox builders and CNC machine manufacturers, selected to contain oil under heavy torsional loads and rapid rotation.'],
    ],

    'clients_intro' => 'Sealing Technologies supplies some of the largest chemical processing, mining, and oil and gas groups, and leading cement producers in India.',
    // group => [[slug (logo file name), display name], …]
    'clients' => [
        'Industrial groups' => [
            ['reliance-industries', 'Reliance Industries'],
            ['adani-group', 'Adani Group'],
            ['vedanta-group', 'Vedanta Group'],
        ],
        'Cement' => [
            ['acc', 'ACC'],
            ['ambuja-cements', 'Ambuja Cements'],
            ['ultratech-cement', 'UltraTech Cement'],
            ['dalmia-bharat', 'Dalmia Bharat'],
            ['india-cements', 'India Cements'],
        ],
    ],

    'sales_force' => [
        'count'  => '16',
        'text'   => 'We do not simply trade parts; we deliver engineered sealing solutions. Our field team is technically trained in mechanical operations.',
        'points' => [
            ['Engineering reviews', 'Technical recommendations made on site.'],
            ['Component cross-referencing', 'Matching legacy specifications to current seals.'],
            ['Rapid deployment', 'Fast dispatch support to keep unplanned downtime short.'],
        ],
    ],

    'inventory' => [
        'range' => 'From 10 mm to 1000 mm outside diameter',
        'text'  => 'The central hub in Vadodara holds a controlled, ready-to-ship inventory of thousands of individual items — standard metric seals and imperial sizes, in materials such as FKM (Viton™), PTFE and NBR.',
    ],

    'advantages' => [
        ['Genuine technology', 'Direct access to Parker Hannifin product lines.'],
        ['Less downtime', 'Seal kits structured for immediate refits.'],
        ['Vadodara hub', 'A central warehouse supplying customers across India.'],
    ],
];
