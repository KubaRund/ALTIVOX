<?php
require __DIR__.'/bootstrap.php'; av_require_auth();
$data=['exported_at'=>date(DATE_ATOM),'records'=>av_records(),'analytics'=>av_read_json('analytics',[]),'404'=>av_read_json('404',[])];
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="altivox-backup-'.date('Ymd-His').'.json"');
echo json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);