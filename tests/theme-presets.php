<?php
require __DIR__.'/../www/admin/theme-lib.php';
foreach(av_theme_presets() as $name=>$preset) { av_theme_validate($preset);echo $name,PHP_EOL; }
