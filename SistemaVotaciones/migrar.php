<?php
/**
 * 🔄 SCRIPT DE MIGRACIÓN
 * 
 * Este script limpia los datos mal guardados donde el bloque 
 * estaba mezclado con el nombre del congresista.
 * 
 * INSTRUCCIONES:
 * 1. Hacer backup de la base de datos antes de ejecutar
 * 2. Ejecutar: http://localhost/congreso/migrar_bloques.php
 * 3. Verificar los resultados
 */

require_once 'config.php';

// Configuración
set_time_limit(300); // 5 minutos máximo
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Lista de bloques conocidos (del más largo al más corto)
$BLOQUES_CONOCIDOS = [
    'COALICIÓN MOVIMIENTO POLÍTICO WINAQ - UNIDAD REVOLUCIONARIA NACIONAL GUATEMALTECA',
    'VAMOS POR UNA GUATEMALA DIFERENTE',
    'PARTIDO POLÍTICO VISIÓN CON VALORES',
    'UNIDAD NACIONAL DE LA ESPERANZA',
    'VOLUNTAD, OPORTUNIDAD Y SOLIDARIDAD',
    'COMPROMISO, RENOVACIÓN Y ORDEN',
    'PARTIDO POLÍTICO NOSOTROS',
    'BIENESTAR NACIONAL',
    'COMUNIDAD ELEFANTE',
    'PARTIDO UNIONISTA',
    'PARTIDO VALOR',
    'PARTIDO AZUL',
    'INDEPENDIENTES',
    'VICTORIA',
    'CAMBIO',
    'CABAL',
    'TODOS',
];

function normalizar($texto) {
    $texto = mb_strtoupper($texto, 'UTF-8');
    $buscar  = ['Á','É','Í','Ó','Ú','Ñ','á','é','í','ó','ú','ñ'];
    $reemplazar = ['A','E','I','O','U','N','a','e','i','o','u','n'];
    return str_replace($buscar, $reemplazar, $texto);
}

function detectarBloqueEnNombre($nombre, $bloques) {
    $nombreNorm = normalizar($nombre);
    
    foreach ($bloques as $bloque) {
        $bloqueNorm = normalizar($bloque);
        if (strpos($nombreNorm, $bloqueNorm) !== false) {
            return $bloque;
        }
    }
    
    return null;
}

function limpiarNombre($nombre, $bloque) {
    if (!$bloque) return trim($nombre);
    
    $nombreLimpio = $nombre;
    foreach (explode(' ', $bloque) as $palabra) {
        if (strlen($palabra) > 3) {
            $nombreLimpio = preg_replace('/\b' . preg_quote($palabra, '/') . '\b/iu', '', $nombreLimpio);
        }
    }
    
    return trim(preg_replace('/\s+/', ' ', $nombreLimpio));
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<title>Migración de Bloques</title>";
echo "<style>
body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
.container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #2563eb; border-bottom: 3px solid #2563eb; padding-bottom: 10px; }
.alert { padding: 15px; margin: 15px 0; border-radius: 8px; border-left: 4px solid; }
.success { background: #d1fae5; border-color: #10b981; color: #065f46; }
.warning { background: #fef3c7; border-color: #f59e0b; color: #92400e; }
.error { background: #fee2e2; border-color: #ef4444; color: #991b1b; }
.info { background: #dbeafe; border-color: #3b82f6; color: #1e40af; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
th { background: #f8fafc; font-weight: 600; color: #475569; }
tr:hover { background: #f8fafc; }
.badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-warning { background: #fef3c7; color: #92400e; }
.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
.stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; }
.stat-card h3 { margin: 0; font-size: 36px; }
.stat-card p { margin: 5px 0 0 0; opacity: 0.9; }
pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 8px; overflow-x: auto; }
.progress { background: #e5e7eb; border-radius: 10px; height: 30px; overflow: hidden; margin: 20px 0; }
.progress-bar { background: linear-gradient(90deg, #3b82f6, #2563eb); height: 100%; text-align: center; line-height: 30px; color: white; font-weight: 600; transition: width 0.3s; }
</style></head><body><div class='container'>";

echo "<h1>🔄 Migración de Datos: Separación de Congresistas y Bloques</h1>";
echo "<p><strong>Fecha:</strong> " . date('d/m/Y H:i:s') . "</p>";

try {
    $db = getDB();
    $db->beginTransaction();
    
    echo "<div class='alert info'>📋 <strong>Iniciando migración...</strong></div>";
    
    // 1. Analizar congresistas actuales
    echo "<h2>1️⃣ Análisis de Datos Actuales</h2>";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM congresistas");
    $totalCongresistas = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM bloques");
    $totalBloques = $stmt->fetch()['total'];
    
    echo "<div class='stats'>";
    echo "<div class='stat-card'><h3>$totalCongresistas</h3><p>Congresistas Registrados</p></div>";
    echo "<div class='stat-card' style='background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);'><h3>$totalBloques</h3><p>Bloques Existentes</p></div>";
    echo "</div>";
    
    // 2. Procesar congresistas
    echo "<h2>2️⃣ Procesando Congresistas</h2>";
    echo "<div class='alert info'>🔍 Buscando bloques mezclados con nombres...</div>";
    
    $stmt = $db->query("SELECT id, nombre, nombre_normalizado FROM congresistas ORDER BY nombre");
    $congresistas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $actualizados = 0;
    $sinCambios = 0;
    $cambios = [];
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Nombre Original</th><th>Bloque Detectado</th><th>Nombre Limpio</th><th>Estado</th></tr>";
    
    foreach ($congresistas as $c) {
        $bloqueDetectado = detectarBloqueEnNombre($c['nombre'], $BLOQUES_CONOCIDOS);
        
        if ($bloqueDetectado) {
            $nombreLimpio = limpiarNombre($c['nombre'], $bloqueDetectado);
            
            // Validar que el nombre limpio tenga al menos 2 palabras
            if (count(explode(' ', $nombreLimpio)) >= 2) {
                $normLimpio = mb_strtolower(trim(preg_replace('/\s+/', ' ', $nombreLimpio)), 'UTF-8');
                
                // Actualizar congresista
                $stmtUpdate = $db->prepare("UPDATE congresistas SET nombre = ?, nombre_normalizado = ? WHERE id = ?");
                $stmtUpdate->execute([$nombreLimpio, $normLimpio, $c['id']]);
                
                $actualizados++;
                $cambios[] = [
                    'id' => $c['id'],
                    'original' => $c['nombre'],
                    'limpio' => $nombreLimpio,
                    'bloque' => $bloqueDetectado
                ];
                
                echo "<tr>";
                echo "<td>{$c['id']}</td>";
                echo "<td>" . htmlspecialchars(substr($c['nombre'], 0, 50)) . "</td>";
                echo "<td><span class='badge badge-warning'>" . htmlspecialchars($bloqueDetectado) . "</span></td>";
                echo "<td><strong>" . htmlspecialchars($nombreLimpio) . "</strong></td>";
                echo "<td><span class='badge badge-success'>✅ Actualizado</span></td>";
                echo "</tr>";
            } else {
                $sinCambios++;
                echo "<tr>";
                echo "<td>{$c['id']}</td>";
                echo "<td>" . htmlspecialchars($c['nombre']) . "</td>";
                echo "<td>-</td>";
                echo "<td>-</td>";
                echo "<td><span class='badge badge-warning'>⚠️ Nombre inválido</span></td>";
                echo "</tr>";
            }
        } else {
            $sinCambios++;
        }
    }
    
    echo "</table>";
    
    // 3. Asegurar que todos los bloques existen
    echo "<h2>3️⃣ Verificando Bloques en la Base de Datos</h2>";
    
    $bloquesCreados = 0;
    foreach ($BLOQUES_CONOCIDOS as $nombreBloque) {
        $stmt = $db->prepare("SELECT id FROM bloques WHERE nombre = ?");
        $stmt->execute([$nombreBloque]);
        
        if (!$stmt->fetch()) {
            $stmtInsert = $db->prepare("INSERT INTO bloques (nombre) VALUES (?)");
            $stmtInsert->execute([$nombreBloque]);
            $bloquesCreados++;
            echo "<div class='alert success'>✅ Bloque creado: <strong>$nombreBloque</strong></div>";
        }
    }
    
    if ($bloquesCreados == 0) {
        echo "<div class='alert info'>ℹ️ Todos los bloques ya existían en la base de datos</div>";
    }
    
    // 4. Actualizar votos existentes (opcional - si hay datos antiguos)
    echo "<h2>4️⃣ Actualizando Relaciones en Votos</h2>";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM votos WHERE bloque_id IS NULL OR bloque_id = 0");
    $votosSinBloque = $stmt->fetch()['total'];
    
    if ($votosSinBloque > 0) {
        echo "<div class='alert warning'>⚠️ Se encontraron <strong>$votosSinBloque votos</strong> sin bloque asignado. Se recomienda reprocesar los PDFs.</div>";
    } else {
        echo "<div class='alert success'>✅ Todos los votos tienen bloques asignados correctamente</div>";
    }
    
    // Commit de cambios
    $db->commit();
    
    echo "<h2>✅ Migración Completada</h2>";
    echo "<div class='stats'>";
    echo "<div class='stat-card' style='background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);'><h3>$actualizados</h3><p>Congresistas Actualizados</p></div>";
    echo "<div class='stat-card' style='background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);'><h3>$bloquesCreados</h3><p>Bloques Creados</p></div>";
    echo "<div class='stat-card' style='background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);'><h3>$sinCambios</h3><p>Sin Cambios</p></div>";
    echo "</div>";
    
    echo "<div class='alert success'>";
    echo "<h3>✅ ¡Migración exitosa!</h3>";
    echo "<p><strong>Próximos pasos:</strong></p>";
    echo "<ol>";
    echo "<li>Verificar los datos en: <a href='congresistas.php'>Ver Congresistas</a> | <a href='bloques.php'>Ver Bloques</a></li>";
    echo "<li>Los nuevos PDFs que se carguen usarán el sistema corregido automáticamente</li>";
    echo "<li>Si hay votos sin bloque, considera reprocesar los PDFs antiguos</li>";
    echo "</ol>";
    echo "</div>";
    
    // Resumen de cambios
    if (count($cambios) > 0) {
        echo "<h2>📝 Resumen de Cambios Realizados</h2>";
        echo "<details><summary><strong>Ver detalles (" . count($cambios) . " cambios)</strong></summary>";
        echo "<pre>";
        foreach (array_slice($cambios, 0, 20) as $cambio) {
            echo "ID {$cambio['id']}: {$cambio['original']}\n";
            echo "  → Nombre: {$cambio['limpio']}\n";
            echo "  → Bloque: {$cambio['bloque']}\n\n";
        }
        if (count($cambios) > 20) {
            echo "... y " . (count($cambios) - 20) . " cambios más\n";
        }
        echo "</pre>";
        echo "</details>";
    }
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo "<div class='alert error'>";
    echo "<h3>❌ Error en la Migración</h3>";
    echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>No se realizaron cambios en la base de datos (rollback automático)</p>";
    echo "</div>";
}

echo "<hr style='margin: 40px 0;'>";
echo "<p style='text-align: center; color: #64748b;'>Sistema de Votaciones del Congreso de Guatemala | Migración v1.0</p>";
echo "</div></body></html>";
?>