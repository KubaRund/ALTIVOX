<?php
http_response_code(403);header("Cache-Control: no-store");echo "Installation is closed. Use tools/init-admin.py locally.";
