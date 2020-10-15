<?php

require_once __DIR__ . '/lib/byte_safe_strings.php';
require_once __DIR__ . '/lib/cast_to_int.php';
require_once __DIR__ . '/lib/error_polyfill.php';
require_once __DIR__ . '/other/ide_stubs/libsodium.php';
require_once __DIR__ . '/lib/random.php';

$int = random_int(0, 65536);
