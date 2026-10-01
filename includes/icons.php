<?php
/**
 * Inline line icons (24×24, stroke = currentColor). Usage: <?= icon('arrow-right') ?>
 */
defined('ST_APP') || exit;

function icon(string $name, int $size = 20, string $class = ''): string
{
    static $paths = [
        // interface
        'arrow-right'    => '<path d="M4 12h15M13 6l6 6-6 6"/>',
        'arrow-up-right' => '<path d="M7 17 17 7M8 7h9v9"/>',
        'chevron-down'   => '<path d="m6 9 6 6 6-6"/>',
        'check'          => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'menu'           => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'close'          => '<path d="M5 5l14 14M19 5 5 19"/>',
        'phone'          => '<path d="M21 16.5v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 1.1 3.7 2 2 0 0 1 3.1 1.5h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.100L7 9.400a16 16 0 0 0 6 6l1.300-1.300a2 2 0 0 1 2.100-.5c.9.300 1.800.600 2.800.700a2 2 0 0 1 1.800 2.200Z" transform="translate(.9 .5)"/>',
        'mail'           => '<rect x="2.500" y="4.500" width="19" height="15" rx="1"/><path d="m3 6 9 7 9-7"/>',
        'map-pin'        => '<path d="M12 21.500s7-6.200 7-11.500a7 7 0 1 0-14 0c0 5.300 7 11.500 7 11.500Z"/><circle cx="12" cy="10" r="2.500"/>',
        'clock'          => '<circle cx="12" cy="12" r="9.500"/><path d="M12 6.500V12l3.500 2"/>',
        'upload'         => '<path d="M12 16V4M7 8.500 12 3.500l5 5M4 15.500v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/>',
        'file'           => '<path d="M14 2.500H6.500a1 1 0 0 0-1 1v17a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1V7Z"/><path d="M14 2.500V7h4.500M9 13h6M9 17h6"/>',
        'whatsapp'       => '<path d="M3.500 20.500 5 16a8.500 8.500 0 1 1 3.200 3.100Z"/><path d="M9 8.500c0 3 2.500 6 6 6.500l1.200-1.500-2-1.200-.9.800c-.9-.400-1.800-1.300-2.200-2.200l.8-.9L10.700 8Z"/>',
        'linkedin'       => '<rect x="3" y="3" width="18" height="18" rx="1.500"/><path d="M7.500 10.500v6M7.500 7.300v.2M11.500 16.500v-6m0 2.500c0-1.500 1-2.500 2.500-2.500s2.500 1 2.500 2.500v3.500"/>',
        'facebook'       => '<path d="M14.500 21v-7.500h2.500l.5-3h-3V8.700c0-1 .5-1.700 1.700-1.700H17.500V4.300c-.6-.1-1.400-.2-2.200-.2-2.300 0-3.800 1.400-3.800 3.900v2.500H9v3h2.500V21"/>',
        'instagram'      => '<rect x="3.500" y="3.500" width="17" height="17" rx="4.500"/><circle cx="12" cy="12" r="3.800"/><path d="M17 7v.1"/>',
        'youtube'        => '<rect x="2.500" y="5.500" width="19" height="13" rx="3.500"/><path d="m10.200 9.300 4.800 2.700-4.800 2.700Z"/>',
        // shop
        'cart'           => '<path d="M2.500 3.500h3l2.300 11.500h10.700l2-8.500H6.300"/><circle cx="9" cy="19.500" r="1.500"/><circle cx="17.500" cy="19.500" r="1.500"/>',
        'truck'          => '<path d="M2.500 6.500h11v10h-11ZM13.500 9.500h4l3.500 3.500v3.500h-7.500"/><circle cx="7" cy="17.500" r="1.800"/><circle cx="17" cy="17.500" r="1.800"/>',
        'ruler'          => '<path d="m3 16.500 13.500-13.500 4.500 4.500L7.500 21Z"/><path d="m7.500 12 2 2M10.500 9l1.500 1.500M13.500 6l2 2"/>',
        'search'         => '<circle cx="10.500" cy="10.500" r="7"/><path d="m16 16 5 5"/>',
        'returns'        => '<path d="M4 9.500h11a5 5 0 0 1 0 10H8"/><path d="m8 5.500-4 4 4 4"/>',
        'camera'         => '<path d="M3 8a1.500 1.500 0 0 1 1.500-1.500h3l1.500-2.500h6l1.500 2.500h3A1.500 1.500 0 0 1 21 8v10.500a1.500 1.500 0 0 1-1.500 1.500h-15A1.500 1.500 0 0 1 3 18.500Z"/><circle cx="12" cy="13" r="3.800"/>',
        'card'           => '<rect x="2.500" y="5" width="19" height="14" rx="1.500"/><path d="M2.500 10h19M6.500 15h4"/>',
        'bank'           => '<path d="M3 9.500 12 4l9 5.500M4.500 9.500h15M6 12.500v5M10 12.500v5M14 12.500v5M18 12.500v5M3.500 20.500h17"/>',
        'trash'          => '<path d="M4 6.500h16M9.500 6.500V4h5v2.500M6.500 6.500l1 14h9l1-14M10 10.500v6M14 10.500v6"/>',
        'box'            => '<path d="M3 7.500 12 3l9 4.500v9L12 21l-9-4.500Z"/><path d="M3 7.500 12 12l9-4.500M12 12v9M7.500 5.200l9 4.500"/>',
        'lock'           => '<rect x="4.500" y="10.500" width="15" height="10" rx="1.500"/><path d="M8 10.500V7.500a4 4 0 0 1 8 0v3"/>',
        'info'           => '<circle cx="12" cy="12" r="9.500"/><path d="M12 11v6M12 7.300v.2"/>',
        // value proposition
        'experience'     => '<circle cx="12" cy="12" r="9.500"/><path d="M12 6.500V12l3.500 2"/><path d="M12 2.500v1.500M21.500 12H20M12 21.500V20M2.500 12H4"/>',
        'network'        => '<circle cx="12" cy="5" r="2.200"/><circle cx="5" cy="18" r="2.200"/><circle cx="19" cy="18" r="2.200"/><circle cx="12" cy="13" r="1.600"/><path d="M12 7.200v4.200M10.700 14 6.500 16.500M13.300 14l4.200 2.500"/>',
        'target'         => '<circle cx="12" cy="12" r="9.500"/><circle cx="12" cy="12" r="5.500"/><circle cx="12" cy="12" r="1.500"/><path d="M12 .8v3.700M12 19.500v3.700M.8 12h3.700M19.500 12h3.700"/>',
        'layers'         => '<path d="m12 3 9.500 5-9.500 5-9.500-5Z"/><path d="m2.500 12.500 9.500 5 9.500-5M2.500 16.700l9.500 5 9.500-5"/>',
        'seal'           => '<circle cx="12" cy="12" r="9.500"/><circle cx="12" cy="12" r="5"/><path d="M12 2.500V7M12 17v4.500"/>',
        'support'        => '<path d="M4 14v-2.500a8 8 0 0 1 16 0V14"/><rect x="2.500" y="13.500" width="4" height="6" rx="1"/><rect x="17.500" y="13.500" width="4" height="6" rx="1"/><path d="M19.500 19.500c0 1.500-1.500 2.500-4 2.500H13"/>',
        'sourcing'       => '<path d="M3 7.500 12 3l9 4.500v9L12 21l-9-4.500Z"/><path d="M3 7.500 12 12l9-4.500M12 12v9M7.500 5.200l9 4.500"/>',
        'b2b'            => '<path d="M3.500 21.500v-15l8-3v18M11.500 9.500l9 2.500v9.500M2 21.500h20"/><path d="M6.500 9v.1M6.500 13v.1M6.500 17v.1M15.500 15v.1M15.500 18v.1"/>',
        // applications
        'pump'           => '<circle cx="10" cy="13" r="6.500"/><circle cx="10" cy="13" r="2"/><path d="M10 6.500V3.500h8v4M16.500 13h5.500M3 21.500h14"/>',
        'gear'           => '<circle cx="12" cy="12" r="3.200"/><path d="M12 2.500v3M12 18.500v3M2.500 12h3M18.500 12h3M5.300 5.300l2.100 2.100M16.600 16.600l2.100 2.100M5.300 18.700l2.100-2.100M16.600 7.400l2.100-2.100"/><circle cx="12" cy="12" r="6.800"/>',
        'motor'          => '<rect x="4" y="7" width="13" height="10" rx="1"/><path d="M17 12h5M7 7v10M10 7v10M13 7v10M6 17v3h9v-3M2 10v4"/>',
        'cylinder'       => '<rect x="2.500" y="8" width="13" height="8" rx="1"/><path d="M15.500 12H22M19.500 9.500v5M6 8v8M9 12h6.500"/>',
        'rotary'         => '<path d="M20.500 12a8.500 8.500 0 1 1-2.500-6"/><path d="M20.500 3.500V7H17"/><circle cx="12" cy="12" r="2.500"/>',
        'mill'           => '<circle cx="8" cy="8" r="4.500"/><circle cx="16" cy="16" r="4.500"/><path d="M2.500 15.500 12 12l9.500-3.500"/>',
        'kiln'           => '<path d="M2.500 15.500 19 8l2 4.500L4.500 20Z"/><path d="M7 13.500l2 4.500M12 11.200l2 4.500M4 21.500h6M16 21.500h5"/>',
        'mining'         => '<path d="M2.500 19.500h19M5 19.500l5-11 4 6 2.500-3 3 8"/><path d="M14 4.500l3 3M17 4.500l-3 3"/>',
        'excavator'      => '<rect x="3" y="12.500" width="9" height="4.500" rx="1"/><path d="M2.500 20.500h10.500M7.500 12.500v-4h4v4M11.500 9.500l6-5 3 5.500-2 3-2.500-1.500 1.500-2.500-1.500-2"/>',
        'press'          => '<path d="M4 3.500h16M6 3.500v17M18 3.500v17M4 20.500h16M9 3.500v6h6v-6M12 9.500v4M8.500 13.500h7M8 17.500h8"/>',
        'conveyor'       => '<rect x="2.500" y="13.500" width="19" height="5" rx="2.500"/><circle cx="6" cy="16" r=".8"/><circle cx="12" cy="16" r=".8"/><circle cx="18" cy="16" r=".8"/><path d="M6.500 10.500h5v-4h-5ZM14 10.500h4v-3h-4Z"/>',
        'factory'        => '<path d="M2.500 21.500v-10l6 3.500v-3.500l6 3.500v-3.500l7 4v6ZM2.500 21.500h19"/><path d="M5 11.500v-8h2.500v9.500"/>',
    ];

    $body = $paths[$name] ?? $paths['seal'];
    return '<svg class="icon' . ($class !== '' ? ' ' . e($class) : '') . '" width="' . $size . '" height="' . $size
        . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . $body . '</svg>';
}
