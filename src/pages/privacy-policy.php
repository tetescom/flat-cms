<?php
$__pf = dirname(__DIR__) . '/data/pages/privacy-policy.json';
$page_data = is_file($__pf) ? json_decode(file_get_contents($__pf), true) : null;
include dirname(__DIR__) . '/php/page-template.php';
