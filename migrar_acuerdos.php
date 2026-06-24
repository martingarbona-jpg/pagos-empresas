<?php

require_once __DIR__ . "/db.php";

// Cambiar a true solamente cuando ya se haya revisado la salida del modo prueba.
$migrar = false;

$tiposAcuerdo = ["Obra Social", "Sindicato", "Mutual"];
$resumen = [
    "empresas_revisadas" => 0,
    "empresas_con_acuerdos" => 0,
    "acuerdos_encontrados" => 0,
    "acuerdos_migrados" => 0,
    "duplicados_omitidos" => 0,
    "invalidos_omitidos" => 0,
    "errores" => 0
];
$detalle = [];

function salidaLinea($texto = "") {
    echo htmlspecialchars($texto, ENT_QUOTES, "UTF-8") . (PHP_SAPI === "cli" ? PHP_EOL : "<br>");
}

function salidaTitulo($texto) {
    if (PHP_SAPI === "cli") {
        echo PHP_EOL . "== " . $texto . " ==" . PHP_EOL;
        return;
    }

    echo "<h2>" . htmlspecialchars($texto, ENT_QUOTES, "UTF-8") . "</h2>";
}

function valorTexto($valor) {
    if ($valor === null) return "";
    return trim((string)$valor);
}

function columnaExiste($columnas, $nombre) {
    return in_array($nombre, $columnas, true);
}

function normalizarPeriodoMigracion($periodo) {
    $periodo = valorTexto($periodo);
    if (preg_match('/^\d{4}-\d{2}$/', $periodo)) {
        return substr($periodo, 5, 2) . "/" . substr($periodo, 2, 2);
    }
    if (preg_match('/^\d{2}\/\d{2}$/', $periodo)) {
        return $periodo;
    }
    if (preg_match('/^\d{2}\/\d{2}\/\d{2}$/', $periodo)) {
        return substr($periodo, 3, 5);
    }
    return $periodo;
}

function detectarEmpresaIdMigracion($empresa) {
    $preferidos = [
        "empresa_id",
        "id_empresa",
        "empresaId",
        "empresa_codigo",
        "codigo_empresa",
        "uuid",
        "uid",
        "id_textual",
        "id"
    ];

    foreach ($preferidos as $campo) {
        if (!array_key_exists($campo, $empresa)) continue;
        $valor = valorTexto($empresa[$campo]);
        if ($valor === "") continue;
        if (strpos($valor, "emp_") === 0 || !ctype_digit($valor)) {
            return $valor;
        }
    }

    foreach ($preferidos as $campo) {
        if (!array_key_exists($campo, $empresa)) continue;
        $valor = valorTexto($empresa[$campo]);
        if ($valor !== "") return $valor;
    }

    return "";
}

function decodificarAcuerdosMigracion($valor) {
    if (is_array($valor)) return $valor;

    $valor = valorTexto($valor);
    if ($valor === "") return [];

    $decodificado = json_decode($valor, true);
    return is_array($decodificado) ? $decodificado : [];
}

function acuerdoValor($acuerdo, $campos, $default = null) {
    foreach ((array)$campos as $campo) {
        if (is_array($acuerdo) && array_key_exists($campo, $acuerdo)) {
            return $acuerdo[$campo];
        }
    }
    return $default;
}

function acuerdoValidoMigracion($acuerdo) {
    return floatval(acuerdoValor($acuerdo, "monto_total", 0)) > 0
        && intval(acuerdoValor($acuerdo, "cantidad_cuotas", 0)) >= 2
        && floatval(acuerdoValor($acuerdo, "monto_cuota", 0)) > 0;
}

function pagosPreviosParaInsert($acuerdo) {
    if (!is_array($acuerdo) || !array_key_exists("pagos_previos_ids", $acuerdo)) {
        return null;
    }

    return json_encode($acuerdo["pagos_previos_ids"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    salidaLinea("ERROR: db.php no expuso una conexion PDO valida en \$pdo.");
    exit;
}

try {
    $columnas = $pdo->query("SHOW COLUMNS FROM empresas")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    http_response_code(500);
    salidaLinea("ERROR: no se pudieron leer las columnas de empresas: " . $e->getMessage());
    exit;
}

if (!columnaExiste($columnas, "acuerdos")) {
    http_response_code(500);
    salidaLinea("ERROR: la tabla empresas no tiene columna acuerdos.");
    exit;
}

$columnasLectura = array_values(array_unique(array_filter([
    columnaExiste($columnas, "id") ? "id" : null,
    columnaExiste($columnas, "empresa_id") ? "empresa_id" : null,
    columnaExiste($columnas, "id_empresa") ? "id_empresa" : null,
    columnaExiste($columnas, "empresaId") ? "empresaId" : null,
    columnaExiste($columnas, "empresa_codigo") ? "empresa_codigo" : null,
    columnaExiste($columnas, "codigo_empresa") ? "codigo_empresa" : null,
    columnaExiste($columnas, "uuid") ? "uuid" : null,
    columnaExiste($columnas, "uid") ? "uid" : null,
    columnaExiste($columnas, "id_textual") ? "id_textual" : null,
    columnaExiste($columnas, "razon") ? "razon" : null,
    columnaExiste($columnas, "razon_social") ? "razon_social" : null,
    columnaExiste($columnas, "cuit") ? "cuit" : null,
    "acuerdos"
])));

$selectEmpresas = "SELECT `" . implode("`, `", $columnasLectura) . "` FROM empresas";
$stmtDuplicado = $pdo->prepare("SELECT id FROM acuerdos WHERE empresa_id = :empresa_id AND tipo = :tipo AND activa = 1 LIMIT 1");
$stmtInsertar = $pdo->prepare(
    "INSERT INTO acuerdos (
        empresa_id,
        tipo,
        monto_total,
        cantidad_cuotas,
        monto_cuota,
        cuotas_pagadas_previas,
        periodo_desde,
        periodo_hasta,
        pagos_previos_ids,
        fecha_carga_acuerdo,
        usuario_carga_acuerdo,
        observaciones,
        activa,
        fecha_creacion,
        fecha_actualizacion
    ) VALUES (
        :empresa_id,
        :tipo,
        :monto_total,
        :cantidad_cuotas,
        :monto_cuota,
        :cuotas_pagadas_previas,
        :periodo_desde,
        :periodo_hasta,
        :pagos_previos_ids,
        COALESCE(:fecha_carga_acuerdo, CURDATE()),
        :usuario_carga_acuerdo,
        :observaciones,
        1,
        NOW(),
        NOW()
    )"
);

if (PHP_SAPI !== "cli") {
    echo "<!doctype html><meta charset=\"utf-8\"><title>Migrar acuerdos</title>";
    echo "<body style=\"font-family:Arial,sans-serif;line-height:1.45;margin:24px\">";
}

salidaTitulo("Migracion de acuerdos historicos");
salidaLinea("Modo: " . ($migrar ? "MIGRACION REAL" : "PRUEBA - no inserta datos"));
salidaLinea("Origen: empresas.acuerdos");
salidaLinea("Destino: acuerdos");

try {
    $empresas = $pdo->query($selectEmpresas);

    foreach ($empresas as $empresa) {
        $resumen["empresas_revisadas"]++;

        $empresaId = detectarEmpresaIdMigracion($empresa);
        $acuerdos = decodificarAcuerdosMigracion($empresa["acuerdos"] ?? null);
        if (!$acuerdos) continue;

        $empresaTieneAcuerdos = false;
        $nombreEmpresa = valorTexto($empresa["razon"] ?? ($empresa["razon_social"] ?? ""));
        $cuitEmpresa = valorTexto($empresa["cuit"] ?? "");
        $descripcionEmpresa = trim($empresaId . ($nombreEmpresa !== "" ? " - " . $nombreEmpresa : "") . ($cuitEmpresa !== "" ? " - CUIT " . $cuitEmpresa : ""));

        foreach ($tiposAcuerdo as $tipo) {
            if (!isset($acuerdos[$tipo]) || !is_array($acuerdos[$tipo])) continue;

            $empresaTieneAcuerdos = true;
            $resumen["acuerdos_encontrados"]++;
            $acuerdo = $acuerdos[$tipo];

            if ($empresaId === "" || !acuerdoValidoMigracion($acuerdo)) {
                $resumen["invalidos_omitidos"]++;
                $detalle[] = "[INVALIDO] " . ($descripcionEmpresa ?: "Empresa sin identificador") . " / " . $tipo
                    . " / total=" . floatval(acuerdoValor($acuerdo, "monto_total", 0))
                    . " / cuotas=" . intval(acuerdoValor($acuerdo, "cantidad_cuotas", 0))
                    . " / cuota=" . floatval(acuerdoValor($acuerdo, "monto_cuota", 0));
                continue;
            }

            $stmtDuplicado->execute([
                "empresa_id" => $empresaId,
                "tipo" => $tipo
            ]);

            if ($stmtDuplicado->fetch()) {
                $resumen["duplicados_omitidos"]++;
                $detalle[] = "[DUPLICADO] " . $descripcionEmpresa . " / " . $tipo;
                continue;
            }

            $params = [
                "empresa_id" => $empresaId,
                "tipo" => $tipo,
                "monto_total" => floatval(acuerdoValor($acuerdo, "monto_total", 0)),
                "cantidad_cuotas" => intval(acuerdoValor($acuerdo, "cantidad_cuotas", 0)),
                "monto_cuota" => floatval(acuerdoValor($acuerdo, "monto_cuota", 0)),
                "cuotas_pagadas_previas" => max(intval(acuerdoValor($acuerdo, "cuotas_pagadas_previas", 0)), 0),
                "periodo_desde" => normalizarPeriodoMigracion(acuerdoValor($acuerdo, "periodo_desde", "")),
                "periodo_hasta" => normalizarPeriodoMigracion(acuerdoValor($acuerdo, "periodo_hasta", "")),
                "pagos_previos_ids" => pagosPreviosParaInsert($acuerdo),
                "fecha_carga_acuerdo" => valorTexto(acuerdoValor($acuerdo, ["fecha_carga_acuerdo", "fecha_carga"], "")) ?: null,
                "usuario_carga_acuerdo" => valorTexto(acuerdoValor($acuerdo, "usuario_carga_acuerdo", "")) ?: "ADMIN",
                "observaciones" => valorTexto(acuerdoValor($acuerdo, ["observaciones", "observaciones_acuerdo"], ""))
            ];

            if ($migrar) {
                try {
                    $stmtInsertar->execute($params);
                    $resumen["acuerdos_migrados"]++;
                    $detalle[] = "[MIGRADO] " . $descripcionEmpresa . " / " . $tipo;
                } catch (Throwable $e) {
                    $resumen["errores"]++;
                    $detalle[] = "[ERROR] " . $descripcionEmpresa . " / " . $tipo . " / " . $e->getMessage();
                }
            } else {
                $resumen["acuerdos_migrados"]++;
                $detalle[] = "[MIGRARIA] " . $descripcionEmpresa . " / " . $tipo
                    . " / total=" . $params["monto_total"]
                    . " / cuotas=" . $params["cantidad_cuotas"]
                    . " / cuota=" . $params["monto_cuota"]
                    . " / periodo=" . $params["periodo_desde"] . " a " . $params["periodo_hasta"];
            }
        }

        if ($empresaTieneAcuerdos) {
            $resumen["empresas_con_acuerdos"]++;
        }
    }
} catch (Throwable $e) {
    $resumen["errores"]++;
    $detalle[] = "[ERROR GENERAL] " . $e->getMessage();
}

salidaTitulo("Detalle");
if ($detalle) {
    foreach ($detalle as $linea) {
        salidaLinea($linea);
    }
} else {
    salidaLinea("No se encontraron acuerdos para migrar.");
}

salidaTitulo("Resumen final");
salidaLinea("Empresas revisadas: " . $resumen["empresas_revisadas"]);
salidaLinea("Empresas con acuerdos: " . $resumen["empresas_con_acuerdos"]);
salidaLinea("Acuerdos encontrados: " . $resumen["acuerdos_encontrados"]);
salidaLinea(($migrar ? "Acuerdos migrados: " : "Acuerdos que migraria: ") . $resumen["acuerdos_migrados"]);
salidaLinea("Duplicados omitidos: " . $resumen["duplicados_omitidos"]);
salidaLinea("Invalidos omitidos: " . $resumen["invalidos_omitidos"]);
salidaLinea("Errores: " . $resumen["errores"]);

if (!$migrar) {
    salidaLinea("");
    salidaLinea("Para ejecutar la migracion real, editar este archivo y cambiar: \$migrar = true;");
}

if (PHP_SAPI !== "cli") {
    echo "</body>";
}
