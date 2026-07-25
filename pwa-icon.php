<?php

/**
 * PWA ikon — yüklenen PNG varsa sunar, yoksa GD ile placeholder üretir.
 */

require_once __DIR__ . '/config/pwa_helpers.php';

$size = isset($_GET['size']) ? (int) $_GET['size'] : 512;

fshop_pwa_serve_icon($size);
