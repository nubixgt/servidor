<?php
$content = file_get_contents('index.php');

$content = str_replace('<th class="d-none d-lg-table-cell">Sesión</th>', '<th>Sesión</th>', $content);
$content = preg_replace('/<td class="d-none d-lg-table-cell">\s*<small class="text-muted">(<\?php echo htmlspecialchars\(\\[.sesion_numero.\] \?\? .-.#.*?)<\/td>/is', '<td><small class="text-muted"></td>', $content);

$content = str_replace('<th class="d-none d-lg-table-cell">Fecha</th>', '<th>Fecha</th>', $content);

$content = str_replace('<th class="text-center d-none d-sm-table-cell" style="width: 100px;">Ausencias</th>', '<th class="text-center" style="width: 100px;">Ausencias</th>', $content);
$content = str_replace('<td class="d-none d-sm-table-cell text-center">', '<td class="text-center">', $content);

$content = str_replace('<th class="text-center d-none d-sm-table-cell">Ausencias</th>', '<th class="text-center">Ausencias</th>', $content);
$content = str_replace('<th class="text-center d-none d-md-table-cell">Total</th>', '<th class="text-center">Total</th>', $content);
$content = str_replace('<td class="text-center d-none d-sm-table-cell">', '<td class="text-center">', $content);
$content = str_replace('<td class="text-center d-none d-md-table-cell">', '<td class="text-center">', $content);
$content = str_replace('<th class="text-center d-none d-sm-table-cell" style="width: 100px;">Total Votos</th>', '<th class="text-center" style="width: 100px;">Total Votos</th>', $content);


file_put_contents('index.php', $content);
echo "Done";
