<?php
$c = curl_init('http://localhost/unificacion_maga/Backend/api/v1/visar/dashboard/stats');
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($c);
print_r($res);
