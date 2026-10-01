<?php
/**
 * Technical illustrations (inline SVG).
 *
 * All drawings are SCHEMATIC cross-sections for orientation only — they are not to
 * scale and carry no dimensions. Colours come from CSS (.il …) so every drawing
 * works on light and dark backgrounds.
 *
 *   illustration('tc')                      small profile, light background
 *   illustration('rod-seal', ['theme' => 'dark'])
 *   illustration_measure(), illustration_cylinder()
 */
defined('ST_APP') || exit;

/** Shared <defs> (hatch patterns, arrow markers). Printed once per page by header.php. */
function illustration_defs(): string
{
    return '<svg class="il-defs" width="0" height="0" aria-hidden="true" focusable="false"><defs>'
        . '<pattern id="st-hatch-l" width="5" height="5" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><rect width="5" height="5" fill="#0B1F33" fill-opacity=".05"/><line x1="0" y1="0" x2="0" y2="5" stroke="#0B1F33" stroke-opacity=".55" stroke-width="1"/></pattern>'
        . '<pattern id="st-hatch-d" width="5" height="5" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><rect width="5" height="5" fill="#fff" fill-opacity=".06"/><line x1="0" y1="0" x2="0" y2="5" stroke="#fff" stroke-opacity=".6" stroke-width="1"/></pattern>'
        . '<pattern id="st-metal-l" width="9" height="9" patternUnits="userSpaceOnUse" patternTransform="rotate(-45)"><line x1="0" y1="0" x2="0" y2="9" stroke="#0B1F33" stroke-opacity=".28" stroke-width="1"/></pattern>'
        . '<pattern id="st-metal-d" width="9" height="9" patternUnits="userSpaceOnUse" patternTransform="rotate(-45)"><line x1="0" y1="0" x2="0" y2="9" stroke="#fff" stroke-opacity=".26" stroke-width="1"/></pattern>'
        . '<marker id="st-arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 1.500 9 5 0 8.500Z" fill="#F47A20"/></marker>'
        . '</defs></svg>';
}

/** Names of the small profile drawings (used by the admin product form). */
function illustration_keys(): array
{
    return [
        'tc' => 'TC rotary shaft seal (double lip, spring)', 'single-lip' => 'Single-lip rotary shaft seal',
        'double-lip' => 'Double-lip rotary seal, metal-cased', 'rod-seal' => 'Hydraulic rod seal (U-cup)',
        'piston-seal' => 'Hydraulic piston seal', 'wiper' => 'Rod wiper', 'buffer' => 'Buffer seal',
        'guide-ring' => 'Guide ring', 'wear-ring' => 'Wear ring', 'o-ring' => 'O-ring / static seal',
        'seal-kit' => 'Seal kit', 'reference' => 'Generic outline (no drawing)',
    ];
}

function illustration(string $key, array $opt = []): string
{
    $dark  = ($opt['theme'] ?? 'light') === 'dark';
    $h     = $dark ? 'url(#st-hatch-d)' : 'url(#st-hatch-l)';
    $keys  = illustration_keys();
    if (!isset($keys[$key])) {
        $key = 'reference';
    }
    $label = $opt['label'] ?? ($keys[$key] . ' — schematic cross-section');
    $class = 'il' . ($dark ? ' il--dark' : '') . (!empty($opt['class']) ? ' ' . $opt['class'] : '');

    return '<svg class="' . e($class) . '" viewBox="0 0 200 150" role="img" aria-label="' . e($label) . '">'
        . il_profile($key, $h) . '</svg>';
}

/* Hardware backdrops ------------------------------------------------------- */

function il_hw_rotary(): string
{
    return '<rect class="hw-fill" x="8" y="6" width="184" height="16"/><path class="hw" d="M8 22H192"/>'
        . '<rect class="hw-fill" x="8" y="128" width="184" height="12"/><path class="hw" d="M8 128H192"/>'
        . '<path class="c" d="M4 146H196"/>';
}

function il_hw_gland(): string
{
    return '<path class="hw-fill" d="M8 8H192V102H152V38H52V102H8Z"/><path class="hw" d="M8 102H52V38H152V102H192"/>'
        . '<rect class="hw-fill" x="8" y="110" width="184" height="30"/><path class="hw" d="M8 110H192"/><path class="c" d="M4 146H196"/>';
}

function il_hw_piston(int $bore = 34): string
{
    return '<rect class="hw-fill" x="8" y="8" width="184" height="' . ($bore - 8) . '"/><path class="hw" d="M8 ' . $bore . 'H192"/>'
        . '<path class="hw-fill" d="M8 42H52V108H148V42H192V140H8Z"/><path class="hw" d="M8 42H52V108H148V42H192"/>';
}

/* Profile geometry ---------------------------------------------------------- */

function il_profile(string $key, string $h): string
{
    $tcBody     = 'M48 22H152L156 26V78L170 124L168 128H160L146 98L134 106L92 128L76 108L70 92L72 84H77A11 11 0 0 0 99 84H104L130 70V40H44V26Z';
    $singleBody = 'M48 22H152L156 26V94L134 106L92 128L76 108L70 92L72 84H77A11 11 0 0 0 99 84H104L130 70V40H44V26Z';
    $spring     = '<circle class="s" cx="88" cy="82" r="8.500"/><circle class="s-fill" cx="88" cy="82" r="2.500"/>';
    $insert     = '<path class="m" d="M54 28H148V80H142V34H54Z"/>';
    $ucup       = 'M148 44H86L70 38L62 50L104 74L62 98L68 110L86 104H148Z';

    switch ($key) {
        case 'tc':
            return il_hw_rotary() . '<path class="b" fill="' . $h . '" d="' . $tcBody . '"/>' . $insert . $spring;

        case 'single-lip':
            return il_hw_rotary() . '<path class="b" fill="' . $h . '" d="' . $singleBody . '"/>' . $insert . $spring;


        case 'double-lip':
            return il_hw_rotary()
                . '<path class="b" fill="' . $h . '" d="M150 50V82H156L170 124L168 128H160L146 98L134 106L92 128L76 108L70 92L72 84H77A11 11 0 0 0 99 84H104L130 70V50Z"/>'
                . '<path class="m" d="M46 22H156V82H150V28H46Z"/><path class="m" d="M108 44H150V50H114V62H108Z" opacity=".55"/>' . $spring;









        case 'rod-seal':
            return il_hw_gland() . '<path class="b" fill="' . $h . '" d="' . $ucup . '"/>';

        case 'buffer':
            return il_hw_gland() . '<path class="b" fill="' . $h . '" d="M148 44H86L70 38L62 50L104 74L62 98L68 110L86 104H130V90H148Z"/>'
                . '<rect class="p" x="132" y="92" width="16" height="15"/>';

        case 'wiper':
            return '<path class="hw-fill" d="M8 8H146V80H134V40H64V102H8Z"/><path class="hw" d="M8 102H64V40H134V80H146V8"/>'
                . '<rect class="hw-fill" x="8" y="110" width="184" height="30"/><path class="hw" d="M8 110H192"/><path class="c" d="M4 146H196"/>'
                . '<path class="b" fill="' . $h . '" d="M70 46H128V82L158 104L152 110L140 108L118 92H70Z"/>';

        case 'piston-seal':
            return il_hw_piston() . '<path class="p" d="M60 34H140L144 38V56H56V38Z"/>'
                . '<ellipse class="b" fill="' . $h . '" cx="100" cy="82" rx="30" ry="25"/>';

        case 'guide-ring':
            return '<path class="hw-fill" d="M8 8H192V102H156V84H44V102H8Z"/><path class="hw" d="M8 102H44V84H156V102H192"/>'
                . '<rect class="hw-fill" x="8" y="110" width="184" height="30"/><path class="hw" d="M8 110H192"/><path class="c" d="M4 146H196"/>'
                . '<rect class="b" fill="' . $h . '" x="48" y="86" width="104" height="24"/><path class="b" d="M94 86 106 110"/>';

        case 'wear-ring':
            return '<rect class="hw-fill" x="8" y="8" width="184" height="26"/><path class="hw" d="M8 34H192"/>'
                . '<path class="hw-fill" d="M8 42H44V58H156V42H192V140H8Z"/><path class="hw" d="M8 42H44V58H156V42H192"/>'
                . '<rect class="b" fill="' . $h . '" x="48" y="34" width="104" height="24"/><path class="b" d="M94 34 106 58"/>';

        case 'o-ring':
            return '<rect class="hw-fill" x="8" y="8" width="184" height="42"/><path class="hw" d="M8 50H192"/>'
                . '<path class="hw-fill" d="M8 50H62V104H138V50H192V140H8Z"/><path class="hw" d="M8 50H62V104H138V50H192"/>'
                . '<ellipse class="b" fill="' . $h . '" cx="100" cy="77" rx="30" ry="27"/>';

        case 'seal-kit':
            return '<rect class="hw" x="12" y="14" width="176" height="122" rx="2"/>'
                . '<circle class="b" cx="68" cy="75" r="42" stroke-width="6"/><circle class="s" cx="68" cy="75" r="28" stroke-width="3"/>'
                . '<circle class="b" cx="68" cy="75" r="14" stroke-width="2"/>'
                . '<circle class="b" cx="146" cy="48" r="18" stroke-width="5"/>'
                . '<circle class="b" cx="152" cy="102" r="20" stroke-width="2" stroke-dasharray="110 6"/>'
                . '<circle class="s" cx="118" cy="116" r="7" stroke-width="3"/>';



        case 'reference':
        default:
            return il_hw_rotary()
                . '<rect class="b" x="52" y="22" width="100" height="106" stroke-dasharray="6 5"/>'
                . '<path class="d" marker-start="url(#st-arrow)" marker-end="url(#st-arrow)" d="M52 112H152"/>'
                . '<path class="d" marker-start="url(#st-arrow)" marker-end="url(#st-arrow)" d="M36 22V128"/>'
                . '<text x="102" y="62" text-anchor="middle">PROFILE</text><text x="102" y="74" text-anchor="middle">AS PER DRAWING</text><text x="102" y="86" text-anchor="middle">OR SAMPLE</text>';
    }
}

/* Large drawings ------------------------------------------------------------ */

/** Measuring diagram: rotary shaft seal, front view and section, with the three sizes to measure. */
function illustration_measure(): string
{
    $h      = 'url(#st-hatch-d)';
    $tcBody = 'M48 22H152L156 26V78L170 124L168 128H160L146 98L134 106L92 128L76 108L70 92L72 84H77A11 11 0 0 0 99 84H104L130 70V40H44V26Z';
    $half   = '<path class="b draw" fill="' . $h . '" d="' . $tcBody . '"/><path class="m" d="M54 28H148V80H142V34H54Z"/>'
            . '<circle class="s" cx="88" cy="82" r="8.500"/><circle class="s-fill" cx="88" cy="82" r="2.500"/>';

    return '<svg class="il il--dark il--hero" viewBox="0 0 660 560" role="img" aria-label="Drawing of a rotary shaft seal showing where to measure the inner diameter, the outer diameter and the width">'
        // front view
        . '<g class="il-front">'
        . '<circle class="b draw" cx="200" cy="280" r="170"/><circle class="b" cx="200" cy="280" r="155" opacity=".7"/>'
        . '<circle class="b" cx="200" cy="280" r="118" opacity=".7"/><circle class="b draw" cx="200" cy="280" r="80"/>'
        . '<circle class="s" cx="200" cy="280" r="110" stroke-dasharray="7 5" stroke-width="1.200"/>'
        . '<circle class="hw" cx="200" cy="280" r="163" stroke-dasharray="3 4"/>'
        . '<path class="c" d="M10 280H390M200 90V470"/>'
        . '<path class="d" d="M200 70V96M200 464V490"/><path class="d" marker-end="url(#st-arrow)" d="M176 78H198"/><path class="d" marker-end="url(#st-arrow)" d="M176 482H198"/>'
        . '<text x="162" y="82">A</text><text x="162" y="486">A</text>'
        . '</g>'
        // section A–A
        . '<g class="il-section">'
        . '<path class="c" d="M410 280H580"/>'
        . '<rect class="hw-fill" x="420" y="200" width="150" height="160"/><path class="hw" d="M420 200H570M420 360H570"/>'
        . '<g transform="translate(402.600 91.300) scale(.849)">' . $half . '</g>'
        . '<g transform="translate(402.600 468.700) scale(.849 -.849)">' . $half . '</g>'
        . '</g>'
        // dimensions
        . '<g class="il-dims">'
        . '<path class="d thin" d="M440 110V72M535 110V72"/><path class="d" marker-start="url(#st-arrow)" marker-end="url(#st-arrow)" d="M440 80H535"/>'
        . '<text x="487" y="66" text-anchor="middle">WIDTH</text>'
        . '<path class="d thin" d="M552 200H596M552 360H596"/><path class="d" marker-start="url(#st-arrow)" marker-end="url(#st-arrow)" d="M588 200V360"/>'
        . '<text transform="translate(602 280) rotate(90)" text-anchor="middle">INNER DIAMETER</text>'
        . '<path class="d thin" d="M540 110H636M540 450H636"/><path class="d" marker-start="url(#st-arrow)" marker-end="url(#st-arrow)" d="M628 110V450"/>'
        . '<text transform="translate(642 280) rotate(90)" text-anchor="middle">OUTER DIAMETER</text>'
        . '</g>'
        // title block
        . '<g class="il-title"><path class="hw" d="M10 520H650M10 548H650M10 520V548M650 520V548M250 520V548M470 520V548"/>'
        . '<text x="22" y="538">ROTARY SHAFT SEAL · TYPE TC</text><text x="262" y="538">FRONT VIEW · SECTION A–A</text><text x="482" y="538">NOT TO SCALE</text></g>'
        . '</svg>';
}

/** Hydraulic cylinder cut-away with numbered seal positions (numbers match the HTML legend). */
function illustration_cylinder(string $theme = 'dark'): string
{
    $dark = $theme === 'dark';
    $mh   = $dark ? 'url(#st-metal-d)' : 'url(#st-metal-l)';
    $cls  = 'il il--cylinder' . ($dark ? ' il--dark' : '');

    $seal = static fn (int $n, string $shape): string => '<g class="part" data-part="' . $n . '">' . $shape . '</g>';
    $pair = static fn (int $x, int $w, int $yTop, int $hgt): string =>
        '<rect x="' . $x . '" y="' . $yTop . '" width="' . $w . '" height="' . $hgt . '"/>'
        . '<rect x="' . $x . '" y="' . (420 - $yTop - $hgt) . '" width="' . $w . '" height="' . $hgt . '"/>';

    $callout = static fn (int $n, int $cx, int $cy, string $lines): string =>
        '<g class="callout" data-part="' . $n . '"><path class="d" d="' . $lines . '"/>'
        . '<circle class="callout__dot" cx="' . $cx . '" cy="' . $cy . '" r="14"/>'
        . '<text x="' . $cx . '" y="' . ($cy + 4) . '" text-anchor="middle">' . $n . '</text></g>';

    return '<svg class="' . $cls . '" viewBox="0 0 960 420" role="img" aria-label="Schematic cut-away of a hydraulic cylinder showing the positions of the piston seal, wear rings, guide ring, buffer seal, rod seal, wiper and static seals">'
        // pressure chambers
        . '<rect class="oil" x="124" y="130" width="176" height="160"/><rect class="oil" x="420" y="130" width="240" height="50"/><rect class="oil" x="420" y="240" width="240" height="50"/>'
        // barrel
        . '<rect class="b" fill="' . $mh . '" x="120" y="110" width="560" height="20"/><rect class="b" fill="' . $mh . '" x="120" y="290" width="560" height="20"/>'
        // ports
        . '<rect class="b" fill="' . $mh . '" x="150" y="92" width="24" height="18"/><rect class="b" fill="' . $mh . '" x="620" y="92" width="24" height="18"/>'
        // end cap + mounting eye
        . '<rect class="b" fill="' . $mh . '" x="84" y="96" width="40" height="228"/>'
        . '<path class="b" fill="' . $mh . '" d="M84 186H66A26 26 0 1 0 66 234H84Z"/><circle class="b hole" cx="50" cy="210" r="11"/>'
        // piston + rod
        . '<rect class="b" fill="' . $mh . '" x="300" y="132" width="120" height="156"/>'
        . '<rect class="b rod" x="420" y="180" width="462" height="60"/>'
        . '<circle class="b rod" cx="904" cy="210" r="30"/><circle class="b hole" cx="904" cy="210" r="12"/>'
        // head / gland
        . '<path class="b" fill="' . $mh . '" d="M660 130H680V96H800V180H660Z"/><path class="b" fill="' . $mh . '" d="M660 290H680V324H800V240H660Z"/>'
        . '<path class="c" d="M14 210H948"/>'
        // seals
        . $seal(1, $pair(348, 24, 130, 16))
        . $seal(2, $pair(312, 22, 130, 8) . $pair(386, 22, 130, 8))
        . $seal(3, $pair(668, 32, 172, 8))
        . $seal(4, $pair(708, 16, 164, 16))
        . $seal(5, $pair(734, 20, 162, 18))
        . $seal(6, $pair(776, 20, 166, 14))
        . $seal(7, '<circle cx="670" cy="134" r="4.500"/><circle cx="670" cy="286" r="4.500"/><circle cx="330" cy="210" r="0"/>')
        // callouts
        . $callout(1, 360, 44, 'M360 58V130')
        . $callout(2, 360, 376, 'M352 364 323 290M368 364 397 290')
        . $callout(3, 640, 44, 'M646 57 684 172')
        . $callout(4, 704, 44, 'M706 58 716 164')
        . $callout(5, 768, 44, 'M764 57 744 162')
        . $callout(6, 832, 44, 'M826 57 786 166')
        . $callout(7, 600, 376, 'M610 366 670 290')
        . '</svg>';
}

/** Brand mark: a seal ring with one highlighted segment. */
function logo_mark(int $size = 40): string
{
    return '<svg class="brand__mark" width="' . $size . '" height="' . $size . '" viewBox="0 0 44 44" aria-hidden="true" focusable="false">'
        . '<circle cx="22" cy="22" r="17" fill="none" stroke="currentColor" stroke-width="6"/>'
        . '<path d="M22 5a17 17 0 0 1 17 17" fill="none" stroke="#F47A20" stroke-width="6"/>'
        . '<circle cx="22" cy="22" r="7.500" fill="none" stroke="currentColor" stroke-width="2"/>'
        . '</svg>';
}
