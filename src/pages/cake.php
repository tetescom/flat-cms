<?php
$page_data = json_decode(file_get_contents(dirname(__DIR__) . '/data/pages/cake.json'), true);
include dirname(__DIR__) . '/php/page-template.php';
