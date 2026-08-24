<?php
// Deny web access to update staging
header('HTTP/1.0 403 Forbidden');
exit;
