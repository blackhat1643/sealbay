<?php
/**
 * Vercel entry point.
 *
 * Vercel's community PHP runtime (vercel-php) runs PHP's built-in web server with
 * this file as its router, so every request that is not a static asset arrives here.
 * The routing itself is shared with local development: see /router.php.
 */
return require __DIR__ . '/../router.php';
