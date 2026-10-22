<?php
declare(strict_types=1);
require __DIR__.'/../admin/bootstrap.php';
require __DIR__.'/../admin/theme-lib.php';
header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
echo av_theme_css(av_theme_current());
