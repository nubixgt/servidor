<?php
namespace App\Services\Presupuesto;

use App\Repositories\Presupuesto\PresupuestoRepository;
use App\DTOs\Presupuesto\PresupuestoDTO;

class PresupuestoService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new PresupuestoRepository();
    }

    public function getDashboardData($filters = [])
    {
        // 1. Resumen Global (solo Ministerios para evitar doble conteo)
        $summary = $this->repository->getSummary($filters);
        
        // 2. Items para la tabla (por defecto cargamos todos los del dashboard excepto MINISTERIO)
        $itemFilters = $filters;
        if (empty($itemFilters['tipo'])) {
            $itemFilters['tipo_dashboard'] = true;
        }
        $items = $this->repository->getItems($itemFilters);
        
        // 3. Data para la gráfica de Grupos (por defecto tipo GRUPO_GASTO)
        $groupFilters = $filters;
        $groupFilters['tipo'] = 'GRUPO_GASTO';
        $groups = $this->repository->getItems($groupFilters);
        
        return [
            'summary' => $summary ? PresupuestoDTO::mapSummary($summary) : null,
            'items' => PresupuestoDTO::mapCollection($items),
            'groups_summary' => PresupuestoDTO::mapCollection($groups)
        ];
    }

    public function createRecord($data, $user = 'System')
    {
        $id = $this->repository->create($data);
        $this->repository->log([
            'usuario' => $user,
            'accion' => 'CREATE_BUDGET_RECORD',
            'detalles' => "Registro creado para categoría ID: " . $data['categoria_id']
        ]);
        return $id;
    }

    public function updateRecord($id, $data, $user = 'System')
    {
        $this->repository->update($id, $data);
        $this->repository->log([
            'usuario' => $user,
            'accion' => 'UPDATE_BUDGET_RECORD',
            'detalles' => "Registro actualizado ID: " . $id
        ]);
        return true;
    }

    public function deleteRecord($id, $user = 'System')
    {
        $this->repository->delete($id);
        $this->repository->log([
            'usuario' => $user,
            'accion' => 'DELETE_BUDGET_RECORD',
            'detalles' => "Registro eliminado ID: " . $id
        ]);
        return true;
    }

    /**
     * Importa datos desde un Excel parseado en JSON.
     *
     * ESTRUCTURA REAL DEL EXCEL "EP (2) (2).xlsx":
     *
     * Hoja "UNI EJE" → tipo UNIDAD_EJECUTORA
     *   Col A: Unidad Ejecutora (nombre + código, ej: '201 "Administración Financiera..."')
     *   Col E: Asignado
     *   Col F: Modificado
     *   Col G: Vigente
     *   Col H: Devengado
     *   Col I: Saldo por Devengar
     *   Col J: % Ejecución
     *   Col K: % Relativo
     *   Col L: tipo (texto)
     *
     * Hoja "UniEjeYGru_Gas" → tipo GRUPO_GASTO / FUENTE_FINANCIAMIENTO
     *   Col B: Unidad Ejecutora
     *   Col C: Grupo de gasto
     *   Col D: Fuente de financiamiento
     *   Col E: Vigente
     *   Col F: Devengado
     *   Col G: Saldo por Devengar
     *   Col H: % Ejecución
     *   Col I: % Relativo
     *   Col K: Tipo Ejecucion ("Grupo de gasto" o "Fuente de financiamiento")
     *
     * Hoja "MINISTERIOS" → tipo MINISTERIO
     *   Col A: nombre del ministerio
     *   Col B: Asignado
     *   Col C: Modificado
     *   Col D: Vigente
     *   Col E: Devengado
     *   Col F: Saldo por Devengar
     *   Col G: % Ejecución
     */
    public function importData($datos, $tipo, $ejercicio, $limpiarAntes)
    {
        if ($tipo === 'GRUPO_GASTO' || $tipo === 'FUENTE_FINANCIAMIENTO') {
            // En la arquitectura unificada de una sola tabla, los registros de grupos de gasto
            // y fuentes de financiamiento ya se importan de manera consolidada (los 31 registros principales)
            // al importar la hoja "UNI EJE" (tipo UNIDAD_EJECUTORA).
            // Importar la hoja de detalles "UniEjeYGru_Gas" sin columna de unidad_ejecutora_id en la tabla
            // solo generaría duplicados huérfanos que corrompen el dashboard. Por lo tanto, omitimos esta importación.
            return 0;
        }

        if ($limpiarAntes) {
            $this->repository->cleanEjecucion($ejercicio, $tipo);
        }

        $inserted = 0;
        foreach ($datos as $row) {
            $rowTipo = $tipo;
            // Normalize keys: lowercase, remove all non-alphanumeric characters for robust matching
            $rowLower = [];
            foreach ($row as $k => $v) {
                $cleanKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string)$k));
                $rowLower[$cleanKey] = $v;
            }

            $codigo = '';
            $nombre = '';
            $asignado = 0;
            $modificado = 0;
            $vigente = 0;
            $devengado = 0;
            $saldo = 0;
            $pct_ejec = 0;
            $pct_rel = 0;

            if ($rowTipo === 'MINISTERIO') {
                // Hoja MINISTERIOS: Col A=nombre (header 'f')
                $rawName = $this->colValue($rowLower, ['f', 'ministerio', 'nombre', 'descripcion']);
                if (!$rawName || is_numeric($rawName)) continue;
                $rawName = trim($rawName);
                if (stripos($rawName, 'total') !== false && strlen($rawName) < 20) continue;

                $nombre = $rawName;
                $codigo = $this->getMinistryCode($rawName);

                $asignado  = $this->numCol($rowLower, [' asignado ', 'asignado']);
                $modificado = $this->numCol($rowLower, [' modificado ', 'modificado']);
                $vigente   = $this->numCol($rowLower, [' vigente ', 'vigente']);
                $devengado = $this->numCol($rowLower, [' devengado ', 'devengado']);
                $saldo     = $this->numCol($rowLower, [' saldo por devengar ', 'saldo']);
                $pct_ejec  = $this->numCol($rowLower, ['% ejecución', '% ejec']);
                $pct_rel   = $this->numCol($rowLower, ['% relativo']);

            } elseif ($rowTipo === 'GRUPO_GASTO') {
                // Hoja UniEjeYGru_Gas: Col C=Grupo de gasto, E=vigente, F=devengado, G=saldo, H=%ejec, I=%rel
                // Solo importar filas donde Tipo Ejecucion = "Grupo de gasto"
                $tipoEje = strtolower(trim((string)($this->colValue($rowLower, ['tipo ejecucion']) ?? '')));
                if ($tipoEje && strpos($tipoEje, 'grupo') === false) continue;

                $rawName = $this->colValue($rowLower, ['grupo de gasto']);
                if (!$rawName) continue;
                $rawName = trim($rawName);
                if (stripos($rawName, 'total') !== false && strlen($rawName) < 20) continue;

                if (preg_match('/^(\d+)/', $rawName, $m)) {
                    $codigo = $m[1];
                } else {
                    continue;
                }
                $nombre = "Código $codigo";

                $vigente   = $this->numCol($rowLower, [' vigente ', 'vigente']);
                $devengado = $this->numCol($rowLower, [' devengado ', 'devengado']);
                $saldo     = $this->numCol($rowLower, [' saldo por devengar ', 'saldo']);
                $pct_ejec  = $this->numCol($rowLower, ['% ejecución', '% ejecucion']);
                $pct_rel   = $this->numCol($rowLower, ['% relativo']);
                $asignado  = $vigente; // no viene asignado en esta hoja

            } elseif ($rowTipo === 'FUENTE_FINANCIAMIENTO') {
                // Hoja UniEjeYGru_Gas: Col D=Fuente, E=vigente, F=devengado
                $tipoEje = strtolower(trim((string)($this->colValue($rowLower, ['tipo ejecucion']) ?? '')));
                if ($tipoEje && strpos($tipoEje, 'fuente') === false) continue;

                $rawName = $this->colValue($rowLower, ['fuente de financiamiento']);
                if (!$rawName) continue;
                $rawName = trim($rawName);
                if (stripos($rawName, 'total') !== false && strlen($rawName) < 20) continue;

                if (preg_match('/^(\d+)/', $rawName, $m)) {
                    $codigo = $m[1];
                } else {
                    continue;
                }
                $nombre = "Código $codigo";

                $vigente   = $this->numCol($rowLower, [' vigente ', 'vigente']);
                $devengado = $this->numCol($rowLower, [' devengado ', 'devengado']);
                $saldo     = $this->numCol($rowLower, [' saldo por devengar ', 'saldo']);
                $pct_ejec  = $this->numCol($rowLower, ['% ejecución', '% ejecucion']);
                $pct_rel   = $this->numCol($rowLower, ['% relativo']);
                $asignado  = $vigente;

            } else {
                // UNIDAD_EJECUTORA — Hoja "UNI EJE" (Contiene Programa, Unidad, Gasto y Financiamiento)
                // Determinar dinámicamente el tipo real basándose en qué columna tiene datos
                $unidadVal = isset($rowLower['unidadejecutora']) ? trim((string)$rowLower['unidadejecutora']) : '';
                $progVal = isset($rowLower['programa']) ? trim((string)$rowLower['programa']) : '';
                $grupoVal = isset($rowLower['grupodegasto']) ? trim((string)$rowLower['grupodegasto']) : '';
                $fuenteVal = isset($rowLower['fuentedefinanciamiento']) ? trim((string)$rowLower['fuentedefinanciamiento']) : '';

                $realTipo = null;
                $codigoCompleto = '';

                if ($unidadVal !== '') {
                    $realTipo = 'UNIDAD_EJECUTORA';
                    $codigoCompleto = $unidadVal;
                } elseif ($progVal !== '') {
                    $realTipo = 'PROGRAMA';
                    $codigoCompleto = $progVal;
                } elseif ($grupoVal !== '') {
                    $realTipo = 'GRUPO_GASTO';
                    $codigoCompleto = $grupoVal;
                } elseif ($fuenteVal !== '') {
                    $realTipo = 'FUENTE_FINANCIAMIENTO';
                    $codigoCompleto = $fuenteVal;
                } else {
                    continue; // Skip if no column is populated
                }

                if (stripos($codigoCompleto, 'total') !== false && strlen($codigoCompleto) < 20) {
                    continue; // Skip total row
                }

                // Extraer el código numérico del string
                if (!preg_match('/^(\d+)/', $codigoCompleto, $matches)) {
                    continue; // Skip if no numeric prefix code found
                }
                $codigo = $matches[1];
                $nombre = "Código $codigo";

                $asignado   = $this->numCol($rowLower, [' asignado ', 'asignado']);
                $modificado = $this->numCol($rowLower, [' modificado ', 'modificado']);
                $vigente    = $this->numCol($rowLower, [' vigente ', 'vigente']);
                $devengado  = $this->numCol($rowLower, [' devengado ', 'devengado']);
                $saldo      = $this->numCol($rowLower, [' saldo por devengar ', 'saldo']);
                $pct_ejec   = $this->numCol($rowLower, ['% ejecución', '% ejecucion']);
                $pct_rel    = $this->numCol($rowLower, ['% relativo']);
                
                // Usamos el tipo real correspondiente para guardarlo en la categoría correcta
                $rowTipo = $realTipo;
            }

            // Skip rows with no meaningful financial data
            if ($vigente == 0 && $devengado == 0 && $asignado == 0) continue;
            if (empty($nombre)) continue;



            $catId = $this->repository->getCategoriaId($rowTipo, $codigo, $nombre);

            $this->repository->create([
                'categoria_id'    => $catId,
                'ejercicio_fiscal' => $ejercicio,
                'asignado'        => round((float)$asignado, 4),
                'modificado'      => round((float)$modificado, 4),
                'vigente'         => round((float)$vigente, 4),
                'devengado'       => round((float)$devengado, 4),
                'saldo'           => round((float)$saldo, 4),
                'pct_ejec'        => round((float)$pct_ejec, 4),
                'fecha_corte'     => date('Y-m-d H:i:s')
            ]);
            $inserted++;
        }

        $this->repository->log([
            'usuario' => 'System',
            'accion'  => 'IMPORT_BUDGET',
            'detalles' => "Importados $inserted registros para $tipo ($ejercicio)"
        ]);

        return $inserted;
    }

    /**
     * Get a value from a row by trying multiple possible column names.
     */
    private function colValue(array $row, array $keys)
    {
        foreach ($keys as $key) {
            $keyL = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $key));
            // Exact match on normalized key
            if (isset($row[$keyL]) && $row[$keyL] !== null && $row[$keyL] !== '') {
                return $row[$keyL];
            }
            // Partial match on normalized keys
            if (strlen($keyL) > 2) {
                foreach ($row as $rKey => $rVal) {
                    if (strpos($rKey, $keyL) !== false && $rVal !== null && $rVal !== '') {
                        return $rVal;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Get a numeric value from a row by trying multiple possible column names.
     */
    private function numCol(array $row, array $keys): float
    {
        $val = $this->colValue($row, $keys);
        if ($val === null || $val === '') return 0.0;
        if (is_numeric($val)) return (float)$val;
        
        // Clean all non-numeric characters except minus sign (-) and decimal point (.)
        $clean = preg_replace('/[^\d\.\-]/', '', (string)$val);
        return (float)$clean;
    }

    /**
     * Map clean ministry names to their database acronyms.
     */
    private function getMinistryCode($name)
    {
        $clean = mb_strtoupper(trim(preg_replace('/\s+/', ' ', $name)), 'UTF-8');
        
        $map = [
            'DEFENSA' => 'MDN',
            'DESARROLLO' => 'MDS',
            'RELACIONES' => 'MRE',
            'GOBERNACION' => 'MG',
            'GOBERNACIÓN' => 'MG',
            'TRABAJO' => 'MTPS',
            'EDUCACION' => 'ME',
            'EDUCACIÓN' => 'ME',
            'FINANZAS' => 'MFP',
            'ECONOMIA' => 'ME',
            'ECONOMÍA' => 'ME',
            'SALUD' => 'MSPAS',
            'AMBIENTE' => 'MARN',
            'AGRICULTURA' => 'MAGA',
            'ENERGIA' => 'MEM',
            'ENERGÍA' => 'MEM',
            'COMUNICACIONES' => 'MCIV',
            'CULTURA' => 'MCD'
        ];

        foreach ($map as $key => $code) {
            if (strpos($clean, $key) !== false) {
                return $code;
            }
        }

        return 'MIN-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $clean), 0, 10));
    }
}
