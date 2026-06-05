<?php
session_start();

$PASSWORD = "1234"; // CAMBIAR CONTRASEÑA ACÁ

$dataDir = __DIR__ . "/data";
$uploadDir = __DIR__ . "/comprobantes";

$empresasFile = $dataDir . "/empresas.json";
$pagosFile = $dataDir . "/pagos.json";

if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
if (!file_exists($empresasFile)) file_put_contents($empresasFile, "[]");
if (!file_exists($pagosFile)) file_put_contents($pagosFile, "[]");

function e($v) {
    return htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8");
}

function leerJson($file) {
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function guardarJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function dinero($n) {
    return "$" . number_format(floatval($n), 2, ",", ".");
}

function limpiarArchivo($txt) {
    return trim(preg_replace('/[^A-Za-z0-9_\-]/', '_', $txt), "_");
}

function periodoParaInput($periodo) {
    $periodo = trim($periodo ?? "");
    if (preg_match('/^\d{4}-\d{2}$/', $periodo)) {
        return "01/" . substr($periodo, 5, 2) . "/" . substr($periodo, 2, 2);
    }
    if (preg_match('/^\d{2}\/\d{2}$/', $periodo)) {
        return "01/" . $periodo;
    }
    return $periodo;
}

function periodoValido($periodo) {
    $periodo = trim($periodo ?? "");
    if (!preg_match('/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{2}$/', $periodo)) {
        return false;
    }
    [$dia, $mes, $anio] = array_map('intval', explode('/', $periodo));
    return checkdate($mes, $dia, 2000 + $anio);
}

function rutaComprobanteFisico($comprobante, $uploadDir) {
    $comprobante = trim($comprobante ?? "");
    if ($comprobante === "") return null;

    $normalizado = str_replace("\\", "/", $comprobante);
    if (substr($normalizado, 0, 13) !== "comprobantes/") return null;

    $archivo = basename($normalizado);
    if ($archivo === "") return null;

    return $uploadDir . "/" . $archivo;
}

function buscarEmpresa($empresas, $id) {
    foreach ($empresas as $e) {
        if (($e["id"] ?? "") === $id) return $e;
    }
    return null;
}

function totalPagado($pagos, $empresaId, $tipo) {
    $total = 0;
    foreach ($pagos as $p) {
        if (($p["empresa_id"] ?? "") === $empresaId && ($p["tipo"] ?? "") === $tipo) {
            $total += floatval($p["monto"] ?? 0);
        }
    }
    return $total;
}

function eliminarComprobantePago($pago, $uploadDir) {
    $rutaFisica = rutaComprobanteFisico($pago["comprobante"] ?? "", $uploadDir);
    if ($rutaFisica && is_file($rutaFisica)) {
        @unlink($rutaFisica);
    }
}

function acuerdoDefault() {
    return [
        "monto_total" => 0,
        "cantidad_cuotas" => 1,
        "monto_cuota" => 0,
        "cuotas_pagadas_previas" => 0,
        "periodo_desde" => "",
        "periodo_hasta" => "",
        "observaciones" => ""
    ];
}

function acuerdoEmpresa($empresa, $tipo) {
    $base = acuerdoDefault();
    if (isset($empresa["acuerdos"]) && is_array($empresa["acuerdos"]) && isset($empresa["acuerdos"][$tipo]) && is_array($empresa["acuerdos"][$tipo])) {
        return array_merge($base, $empresa["acuerdos"][$tipo]);
    }

    if (!empty($empresa["monto_total"]) || !empty($empresa["monto_cuota"]) || !empty($empresa["periodo_desde"]) || !empty($empresa["periodo_hasta"])) {
        return array_merge($base, [
            "monto_total" => floatval($empresa["monto_total"] ?? 0),
            "cantidad_cuotas" => max(intval($empresa["cantidad_cuotas"] ?? 1), 1),
            "monto_cuota" => floatval($empresa["monto_cuota"] ?? 0),
            "cuotas_pagadas_previas" => max(intval($empresa["cuotas_pagadas_previas"] ?? 0), 0),
            "periodo_desde" => periodoParaInput($empresa["periodo_desde"] ?? ""),
            "periodo_hasta" => periodoParaInput($empresa["periodo_hasta"] ?? ""),
            "observaciones" => $empresa["observaciones_acuerdo"] ?? ""
        ]);
    }

    return $base;
}

function resumenAcuerdosEmpresa($empresa) {
    $partes = [];
    foreach (["Obra Social","Sindicato","Mutual"] as $tipo) {
        $a = acuerdoEmpresa($empresa, $tipo);
        if (floatval($a["monto_total"] ?? 0) <= 0 && floatval($a["monto_cuota"] ?? 0) <= 0) continue;

        $cuotas = max(intval($a["cantidad_cuotas"] ?? 1), 1);
        $plan = $cuotas > 1 ? "Acuerdo" : "Pago único";
        $periodo = trim(($a["periodo_desde"] ?? "") . (($a["periodo_desde"] ?? "") && ($a["periodo_hasta"] ?? "") ? " a " : "") . ($a["periodo_hasta"] ?? ""));
        $detalle = $cuotas > 1 ? ($cuotas . " x " . dinero($a["monto_cuota"] ?? 0)) : dinero($a["monto_total"] ?? 0);
        $partes[] = $tipo . ": " . $plan . " " . $detalle . ($periodo ? " (" . $periodo . ")" : "");
    }
    return $partes ? implode(" | ", $partes) : "Sin acuerdo cargado";
}

if (isset($_POST["login"])) {
    if (($_POST["password"] ?? "") === $PASSWORD) {
        $_SESSION["auth_pagos_empresas"] = true;
        header("Location: index.php");
        exit;
    }
    $error = "Contraseña incorrecta";
}

if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION["auth_pagos_empresas"])) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pagos Empresas</title>
<style>
body{margin:0;font-family:Arial;background:#f3f6f4;display:flex;align-items:center;justify-content:center;height:100vh}
.login{background:white;padding:30px;border-radius:16px;box-shadow:0 8px 25px #0002;width:330px}
h1{color:#087a46;margin-top:0}
input,button{width:100%;padding:12px;margin-top:12px;border-radius:8px;border:1px solid #ccc;box-sizing:border-box}
button{background:#087a46;color:white;border:0;font-weight:bold;cursor:pointer}
.error{color:#b00020;font-weight:bold}
</style>
</head>
<body>
<div class="login">
<h1>Pagos Empresas</h1>
<form method="post">
<input type="password" name="password" placeholder="Contraseña" required>
<button name="login">Ingresar</button>
</form>
<?php if(isset($error)) echo "<p class='error'>".e($error)."</p>"; ?>
</div>
</body>
</html>
<?php exit; }

$empresas = leerJson($empresasFile);
$pagos = leerJson($pagosFile);
$errorEmpresa = "";
$errorPago = "";

if (isset($_POST["guardar_empresa"])) {
    $id = $_POST["empresa_id"] ?: uniqid("emp_");

    $nueva = [
        "id" => $id,
        "razon" => trim($_POST["razon"] ?? ""),
        "cuit" => trim($_POST["cuit"] ?? ""),
        "deuda_os" => floatval($_POST["deuda_os"] ?? 0),
        "deuda_sindicato" => floatval($_POST["deuda_sindicato"] ?? 0),
        "deuda_mutual" => floatval($_POST["deuda_mutual"] ?? 0),
        "observaciones" => trim($_POST["observaciones_empresa"] ?? ""),
        "fecha_carga" => date("Y-m-d H:i:s")
    ];

    if ($errorEmpresa === "") {
        $editado = false;
        foreach ($empresas as $k => $emp) {
            if (($emp["id"] ?? "") === $id) {
                $nueva["fecha_carga"] = $emp["fecha_carga"] ?? date("Y-m-d H:i:s");
                $nueva["acuerdos"] = $emp["acuerdos"] ?? [];
                foreach (["monto_total","cantidad_cuotas","monto_cuota","periodo_desde","periodo_hasta","observaciones_acuerdo"] as $campoViejo) {
                    if (isset($emp[$campoViejo])) $nueva[$campoViejo] = $emp[$campoViejo];
                }
                $empresas[$k] = $nueva;
                $editado = true;
                break;
            }
        }

        if (!$editado) $empresas[] = $nueva;

        guardarJson($empresasFile, $empresas);
        header("Location: index.php");
        exit;
    }
}

if (isset($_POST["guardar_acuerdo"])) {
    $empresaIdAcuerdo = $_POST["acuerdo_empresa_id"] ?? "";
    $tipoAcuerdo = $_POST["acuerdo_tipo"] ?? "";
    $montoTotalAcuerdo = floatval($_POST["acuerdo_monto_total"] ?? 0);
    $cantidadCuotasAcuerdo = intval($_POST["acuerdo_cantidad_cuotas"] ?? 1);
    $montoCuotaAcuerdo = floatval($_POST["acuerdo_monto_cuota"] ?? 0);
    $cuotasPreviasAcuerdo = intval($_POST["acuerdo_cuotas_pagadas_previas"] ?? 0);
    $periodoDesdeAcuerdo = trim($_POST["acuerdo_periodo_desde"] ?? "");
    $periodoHastaAcuerdo = trim($_POST["acuerdo_periodo_hasta"] ?? "");

    if ($empresaIdAcuerdo === "") {
        $errorEmpresa = "Seleccioná una empresa para cargar el acuerdo.";
    } elseif (!in_array($tipoAcuerdo, ["Obra Social","Sindicato","Mutual"], true)) {
        $errorEmpresa = "Seleccioná un tipo de acuerdo válido.";
    } elseif ($montoTotalAcuerdo < 0) {
        $errorEmpresa = "El monto total del acuerdo no puede ser negativo.";
    } elseif ($cantidadCuotasAcuerdo < 1) {
        $errorEmpresa = "La cantidad de cuotas debe ser como mínimo 1.";
    } elseif ($cuotasPreviasAcuerdo < 0) {
        $errorEmpresa = "Las cuotas ya pagadas no pueden ser negativas.";
    } elseif ($cuotasPreviasAcuerdo > $cantidadCuotasAcuerdo) {
        $errorEmpresa = "Las cuotas ya pagadas no pueden superar la cantidad de cuotas.";
    } elseif ($cantidadCuotasAcuerdo > 1 && $montoCuotaAcuerdo <= 0) {
        $errorEmpresa = "Si hay acuerdo, el monto de cada cuota debe ser mayor a 0.";
    } elseif ($cantidadCuotasAcuerdo > 1 && ($periodoDesdeAcuerdo === "" || $periodoHastaAcuerdo === "")) {
        $errorEmpresa = "Si hay acuerdo, el período desde y el período hasta son obligatorios.";
    } elseif ($periodoDesdeAcuerdo !== "" && !periodoValido($periodoDesdeAcuerdo)) {
        $errorEmpresa = "El período desde debe tener formato DD/MM/AA.";
    } elseif ($periodoHastaAcuerdo !== "" && !periodoValido($periodoHastaAcuerdo)) {
        $errorEmpresa = "El período hasta debe tener formato DD/MM/AA.";
    }

    if ($errorEmpresa === "") {
        foreach ($empresas as $k => $emp) {
            if (($emp["id"] ?? "") === $empresaIdAcuerdo) {
                if (!isset($empresas[$k]["acuerdos"]) || !is_array($empresas[$k]["acuerdos"])) {
                    $empresas[$k]["acuerdos"] = [];
                }
                $empresas[$k]["acuerdos"][$tipoAcuerdo] = [
                    "monto_total" => $montoTotalAcuerdo,
                    "cantidad_cuotas" => $cantidadCuotasAcuerdo,
                    "monto_cuota" => $montoCuotaAcuerdo,
                    "cuotas_pagadas_previas" => $cuotasPreviasAcuerdo,
                    "periodo_desde" => $periodoDesdeAcuerdo,
                    "periodo_hasta" => $periodoHastaAcuerdo,
                    "observaciones" => trim($_POST["acuerdo_observaciones"] ?? "")
                ];
                break;
            }
        }

        guardarJson($empresasFile, $empresas);
        header("Location: index.php#cargar-acuerdo");
        exit;
    }
}

if (isset($_POST["guardar_pago"])) {
    $id = $_POST["pago_id"] ?: uniqid("pago_");
    $comprobante = $_POST["comprobante_actual"] ?? "";
    $periodo = trim($_POST["periodo"] ?? "");

    if (!periodoValido($periodo)) {
        $errorPago = "El periodo debe tener formato DD/MM/AA.";
    } elseif (!empty($_FILES["comprobante"]["name"])) {
        $ext = strtolower(pathinfo($_FILES["comprobante"]["name"], PATHINFO_EXTENSION));
        $permitidos = ["pdf", "jpg", "jpeg", "png"];

        if (in_array($ext, $permitidos)) {
            $empresa = buscarEmpresa($empresas, $_POST["empresa_id"]);
            $cuit = preg_replace('/[^0-9]/', '', $empresa["cuit"] ?? "");
            $razon = limpiarArchivo($empresa["razon"] ?? "empresa");
            $nombreArchivo = date("Ymd_His") . "_" . $cuit . "_" . $razon . "." . $ext;

            if (move_uploaded_file($_FILES["comprobante"]["tmp_name"], $uploadDir . "/" . $nombreArchivo)) {
                $comprobante = "comprobantes/" . $nombreArchivo;
            }
        }
    }

    if ($errorPago === "") {
        $nuevo = [
            "id" => $id,
            "empresa_id" => $_POST["empresa_id"] ?? "",
            "fecha" => $_POST["fecha"] ?? "",
            "tipo" => $_POST["tipo"] ?? "",
            "forma_pago" => $_POST["forma_pago"] ?? "",
            "monto" => floatval($_POST["monto"] ?? 0),
            "pago_tipo" => $_POST["pago_tipo"] ?? "Pago total",
            "cuotas" => $_POST["cuotas"] ?? "",
            "periodo" => $periodo,
            "comprobante" => $comprobante,
            "observaciones" => trim($_POST["observaciones_pago"] ?? ""),
            "fecha_carga" => date("Y-m-d H:i:s")
        ];

        $editado = false;
        foreach ($pagos as $k => $p) {
            if (($p["id"] ?? "") === $id) {
                $nuevo["fecha_carga"] = $p["fecha_carga"] ?? date("Y-m-d H:i:s");
                $pagos[$k] = $nuevo;
                $editado = true;
                break;
            }
        }

        if (!$editado) $pagos[] = $nuevo;

        guardarJson($pagosFile, $pagos);
        header("Location: index.php");
        exit;
    }
}

if (isset($_GET["eliminar_empresa"])) {
    $id = $_GET["eliminar_empresa"];
    foreach ($pagos as $p) {
        if (($p["empresa_id"] ?? "") === $id) {
            eliminarComprobantePago($p, $uploadDir);
        }
    }
    $empresas = array_values(array_filter($empresas, fn($e) => ($e["id"] ?? "") !== $id));
    $pagos = array_values(array_filter($pagos, fn($p) => ($p["empresa_id"] ?? "") !== $id));
    guardarJson($empresasFile, $empresas);
    guardarJson($pagosFile, $pagos);
    header("Location: index.php");
    exit;
}

if (isset($_GET["eliminar_pago"])) {
    $id = $_GET["eliminar_pago"];
    foreach ($pagos as $p) {
        if (($p["id"] ?? "") === $id) {
            eliminarComprobantePago($p, $uploadDir);
            break;
        }
    }
    $pagos = array_values(array_filter($pagos, fn($p) => ($p["id"] ?? "") !== $id));
    guardarJson($pagosFile, $pagos);
    header("Location: index.php");
    exit;
}

if (isset($_GET["eliminar_comprobante"])) {
    $id = $_GET["eliminar_comprobante"];

    foreach ($pagos as $k => $p) {
        if (($p["id"] ?? "") === $id) {
            $rutaFisica = rutaComprobanteFisico($p["comprobante"] ?? "", $uploadDir);
            if ($rutaFisica && is_file($rutaFisica)) {
                @unlink($rutaFisica);
            }

            $pagos[$k]["comprobante"] = "";
            break;
        }
    }

    guardarJson($pagosFile, $pagos);
    header("Location: index.php?editar_pago=" . urlencode($id));
    exit;
}

$editarEmpresa = null;
if (isset($_GET["editar_empresa"])) {
    $editarEmpresa = buscarEmpresa($empresas, $_GET["editar_empresa"]);
}

if ($errorEmpresa !== "" && isset($_POST["guardar_empresa"])) {
    $editarEmpresa = [
        "id" => $_POST["empresa_id"] ?? "",
        "razon" => $_POST["razon"] ?? "",
        "cuit" => $_POST["cuit"] ?? "",
        "deuda_os" => $_POST["deuda_os"] ?? "",
        "deuda_sindicato" => $_POST["deuda_sindicato"] ?? "",
        "deuda_mutual" => $_POST["deuda_mutual"] ?? "",
        "observaciones" => $_POST["observaciones_empresa"] ?? ""
    ];
}

$acuerdoForm = [
    "empresa_id" => $_POST["acuerdo_empresa_id"] ?? "",
    "tipo" => $_POST["acuerdo_tipo"] ?? "",
    "monto_total" => $_POST["acuerdo_monto_total"] ?? "",
    "cantidad_cuotas" => $_POST["acuerdo_cantidad_cuotas"] ?? "1",
    "monto_cuota" => $_POST["acuerdo_monto_cuota"] ?? "",
    "cuotas_pagadas_previas" => $_POST["acuerdo_cuotas_pagadas_previas"] ?? "0",
    "periodo_desde" => $_POST["acuerdo_periodo_desde"] ?? "",
    "periodo_hasta" => $_POST["acuerdo_periodo_hasta"] ?? "",
    "observaciones" => $_POST["acuerdo_observaciones"] ?? ""
];

$editarPago = null;
if (isset($_GET["editar_pago"])) {
    foreach ($pagos as $p) {
        if (($p["id"] ?? "") === $_GET["editar_pago"]) {
            $editarPago = $p;
            break;
        }
    }
}

if ($errorPago !== "" && isset($_POST["guardar_pago"])) {
    $editarPago = [
        "id" => $_POST["pago_id"] ?? "",
        "empresa_id" => $_POST["empresa_id"] ?? "",
        "fecha" => $_POST["fecha"] ?? date("Y-m-d"),
        "tipo" => $_POST["tipo"] ?? "",
        "forma_pago" => $_POST["forma_pago"] ?? "",
        "monto" => $_POST["monto"] ?? "",
        "pago_tipo" => $_POST["pago_tipo"] ?? "Pago total",
        "cuotas" => $_POST["cuotas"] ?? "",
        "periodo" => $_POST["periodo"] ?? "",
        "comprobante" => $_POST["comprobante_actual"] ?? "",
        "observaciones" => $_POST["observaciones_pago"] ?? ""
    ];
}

$totalCobrado = 0;
$cobradoOS = 0;
$cobradoSindicato = 0;
$cobradoMutual = 0;
$cantidadPagos = count($pagos);

foreach ($pagos as $p) {
    $monto = floatval($p["monto"] ?? 0);
    $totalCobrado += $monto;

    if (($p["tipo"] ?? "") === "Obra Social") {
        $cobradoOS += $monto;
    } elseif (($p["tipo"] ?? "") === "Sindicato") {
        $cobradoSindicato += $monto;
    } elseif (($p["tipo"] ?? "") === "Mutual") {
        $cobradoMutual += $monto;
    }
}

$totalCobrado = max($totalCobrado, 0);
$cobradoOS = max($cobradoOS, 0);
$cobradoSindicato = max($cobradoSindicato, 0);
$cobradoMutual = max($cobradoMutual, 0);
$tabInicial = $editarPago ? "cargar-pago" : ($editarEmpresa ? "nueva-empresa" : ((isset($_POST["guardar_acuerdo"]) && $errorEmpresa !== "") ? "cargar-acuerdo" : "inicio"));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro de Pagos Empresas</title>
<style>
body{margin:0;font-family:Arial;background:#f3f6f4;color:#222}
header{background:#087a46;color:white;padding:18px 25px;display:flex;justify-content:space-between;align-items:center}
header h1{margin:0;font-size:24px}
header a{color:white;text-decoration:none;font-weight:bold}
main{padding:20px}
.tabs{display:flex;flex-wrap:wrap;gap:8px;background:white;padding:12px 20px;border-bottom:1px solid #dcefe6;position:sticky;top:0;z-index:5}
.tab-btn{width:auto;background:#eaf7f0;color:#087a46;border:1px solid #b9dfcc;padding:10px 13px}
.tab-btn.active{background:#087a46;color:white;border-color:#087a46}
.tab-panel{display:none}
.tab-panel.active{display:block}
.home-actions{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-top:16px}
.home-actions button{min-height:64px;font-size:16px}
.empresa-ficha{background:#f7fbf9;border:1px solid #dcefe6;border-radius:12px;padding:16px;margin:16px 0}
.empresa-ficha-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.search-results{margin-top:12px}
.search-result{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px;border-bottom:1px solid #dcefe6}
.search-result button{width:auto}
.empresa-picker{position:relative}
.empresa-picker-input{width:100%}
.empresa-picker-results{display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:white;border:1px solid #b9dfcc;border-radius:8px;box-shadow:0 8px 20px #0002;max-height:220px;overflow-y:auto;z-index:20}
.empresa-picker-results.active{display:block}
.empresa-picker-option{padding:10px;cursor:pointer;border-bottom:1px solid #eaf7f0}
.empresa-picker-option:hover{background:#eaf7f0;color:#087a46}
.card{background:white;border-radius:16px;box-shadow:0 5px 18px #0001;padding:20px;margin-bottom:20px}
.card-header{display:flex;justify-content:space-between;align-items:center;gap:12px}
.card-header h2{margin:0}
.toggle-card{width:auto;background:#eaf7f0;color:#087a46;border:1px solid #b9dfcc;padding:8px 12px}
.card-body{margin-top:16px}
.card.is-collapsed .card-body{display:none}
.quick-actions{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px}
.quick-actions button{width:auto}
.resumen{display:grid;grid-template-columns:repeat(5,1fr);gap:15px}
.box{background:#eaf7f0;padding:18px;border-radius:14px}
.label{font-size:14px;color:#555}
.num{font-size:26px;font-weight:bold;color:#087a46;margin-top:5px}
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
input,select,textarea,button{padding:10px;border:1px solid #ccc;border-radius:8px;font-size:14px;box-sizing:border-box}
textarea{width:100%;height:65px;margin-top:12px}
button{background:#087a46;color:white;border:0;font-weight:bold;cursor:pointer}
.btn-cancelar{display:inline-block;background:#777;color:white;padding:10px 14px;border-radius:8px;text-decoration:none;margin-left:8px}
.btn-secundario{display:inline-block;background:#eaf7f0;color:#087a46;border:1px solid #b9dfcc;padding:9px 12px;border-radius:8px;text-decoration:none;font-weight:bold;margin-right:8px}
.btn-danger{display:inline-block;background:#b00020;color:white;border:0;padding:9px 12px;border-radius:8px;text-decoration:none;font-weight:bold}
.filters{background:#f7fbf9;border:1px solid #dcefe6;border-radius:12px;padding:14px;margin:12px 0 16px}
.filters-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px;align-items:center}
.filters-grid.empresas{grid-template-columns:1.2fr 1.2fr 2fr 1fr auto}
.filters-grid.informe{grid-template-columns:1fr 1fr auto}
.filters input,.filters select{width:100%;background:white}
.filters button{white-space:nowrap}
.informe-resumen{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin:12px 0 16px}
.informe-resumen .box{padding:14px}
.mini-title{margin:18px 0 8px;color:#087a46}
.btn-small{display:inline-block;background:#087a46;color:white;border:0;padding:7px 10px;border-radius:8px;text-decoration:none;font-weight:bold;cursor:pointer}
.estado{display:inline-block;padding:4px 8px;border-radius:20px;font-weight:bold;font-size:12px}
.estado-ok{background:#eaf7f0;color:#087a46}
.estado-previa{background:#e8f0ff;color:#2255aa}
.estado-parcial{background:#fff4df;color:#b76500}
.estado-deudor{background:#fde7eb;color:#b00020}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left;font-size:14px}
th{background:#087a46;color:white}
.acciones a{text-decoration:none;margin-right:8px;font-size:18px}
.badge{background:#eaf7f0;color:#087a46;padding:4px 8px;border-radius:20px;font-weight:bold;font-size:12px}
.sin{color:#999}
.saldo-ok{color:#087a46;font-weight:bold}
.saldo-debe{color:#b00020;font-weight:bold}
.error{color:#b00020;font-weight:bold}
.fila-oculta{display:none}
@media(max-width:1000px){.grid,.resumen,.home-actions,.empresa-ficha-grid,.filters-grid,.filters-grid.empresas,.filters-grid.informe,.informe-resumen{grid-template-columns:1fr}table{display:block;overflow-x:auto}}
</style>
</head>
<body>

<header>
<h1>Registro de Pagos - Empresas Deudoras</h1>
<a href="?logout=1">Salir</a>
</header>

<nav class="tabs">
<button type="button" class="tab-btn active" data-tab="inicio">Inicio</button>
<button type="button" class="tab-btn" data-tab="buscar-empresa">Buscar empresa</button>
<button type="button" class="tab-btn" data-tab="cargar-pago">Cargar pago</button>
<button type="button" class="tab-btn" data-tab="cargar-acuerdo">Cargar acuerdo</button>
<button type="button" class="tab-btn" data-tab="nueva-empresa">Nueva empresa</button>
<button type="button" class="tab-btn" data-tab="informe-periodo">Informe período</button>
<button type="button" class="tab-btn" data-tab="pagos">Pagos registrados</button>
</nav>

<main>

<section class="tab-panel active" id="tab-inicio">
<div class="card resumen">
<div class="box"><div class="label">Total cobrado</div><div class="num"><?= dinero($totalCobrado) ?></div></div>
<div class="box"><div class="label">Cobrado Obra Social</div><div class="num"><?= dinero($cobradoOS) ?></div></div>
<div class="box"><div class="label">Cobrado Sindicato</div><div class="num"><?= dinero($cobradoSindicato) ?></div></div>
<div class="box"><div class="label">Cobrado Mutual</div><div class="num"><?= dinero($cobradoMutual) ?></div></div>
<div class="box"><div class="label">Pagos registrados</div><div class="num"><?= e($cantidadPagos) ?></div></div>
</div>

<div class="card">
<h2>Inicio</h2>
<input type="text" id="homeEmpresaSearch" placeholder="Buscar rápido por razón social o CUIT">
<div id="homeEmpresaResultados" class="search-results"></div>
<div class="home-actions">
<button type="button" class="tab-jump" data-tab="buscar-empresa">Buscar empresa</button>
<button type="button" class="tab-jump" data-tab="cargar-pago">Cargar pago</button>
<button type="button" class="tab-jump" data-tab="cargar-acuerdo">Cargar acuerdo</button>
<button type="button" class="tab-jump" data-tab="informe-periodo">Informe período</button>
<button type="button" class="tab-jump" data-tab="nueva-empresa">Nueva empresa</button>
</div>
</div>
</section>

<section class="tab-panel" id="tab-nueva-empresa">
<div class="card collapsible-card" id="nueva-empresa" data-card="nueva-empresa">
<div class="card-header">
<h2><?= $editarEmpresa ? "Editar empresa" : "Nueva empresa" ?></h2>
<button type="button" class="toggle-card">Minimizar</button>
</div>
<div class="card-body">

<form method="post" id="empresaForm">
<input type="hidden" name="empresa_id" value="<?= e($editarEmpresa["id"] ?? "") ?>">

<div class="grid">
<input type="text" name="razon" placeholder="Razón social" required value="<?= e($editarEmpresa["razon"] ?? "") ?>">
<input type="text" name="cuit" placeholder="CUIT" required value="<?= e($editarEmpresa["cuit"] ?? "") ?>">
<input type="number" step="0.01" min="0" name="deuda_os" placeholder="Deuda Obra Social" value="<?= e($editarEmpresa["deuda_os"] ?? "") ?>">
<input type="number" step="0.01" min="0" name="deuda_sindicato" placeholder="Deuda Sindicato" value="<?= e($editarEmpresa["deuda_sindicato"] ?? "") ?>">
<input type="number" step="0.01" min="0" name="deuda_mutual" placeholder="Deuda Mutual" value="<?= e($editarEmpresa["deuda_mutual"] ?? "") ?>">
</div>

<textarea name="observaciones_empresa" placeholder="Observaciones empresa"><?= e($editarEmpresa["observaciones"] ?? "") ?></textarea>

<?php if($errorEmpresa !== ""): ?>
<p class="error"><?= e($errorEmpresa) ?></p>
<?php endif; ?>

<br><br>
<button name="guardar_empresa"><?= $editarEmpresa ? "Guardar cambios empresa" : "Guardar empresa" ?></button>
<?php if($editarEmpresa): ?>
<a class="btn-cancelar" href="index.php">Cancelar</a>
<?php endif; ?>
</form>
</div>
</div>
</section>

<section class="tab-panel" id="tab-cargar-pago">
<div class="card collapsible-card" id="cargar-pago" data-card="cargar-pago">
<div class="card-header">
<h2><?= $editarPago ? "Editar pago" : "Cargar pago" ?></h2>
<button type="button" class="toggle-card">Minimizar</button>
</div>
<div class="card-body">

<form method="post" enctype="multipart/form-data">
<input type="hidden" name="pago_id" value="<?= e($editarPago["id"] ?? "") ?>">
<input type="hidden" name="comprobante_actual" value="<?= e($editarPago["comprobante"] ?? "") ?>">

<div class="grid">
<?php $empresaPagoSeleccionada = buscarEmpresa($empresas, $editarPago["empresa_id"] ?? ""); ?>
<div class="empresa-picker" data-hidden-name="empresa_id">
<input type="text" class="empresa-picker-input" placeholder="Buscar empresa por razón social o CUIT" autocomplete="off" value="<?= e($empresaPagoSeleccionada ? (($empresaPagoSeleccionada["razon"] ?? "") . " - " . ($empresaPagoSeleccionada["cuit"] ?? "")) : "") ?>">
<input type="hidden" name="empresa_id" class="empresa-picker-hidden" required value="<?= e($editarPago["empresa_id"] ?? "") ?>">
<div class="empresa-picker-results"></div>
</div>

<input type="date" name="fecha" required value="<?= e($editarPago["fecha"] ?? date("Y-m-d")) ?>">

<select name="tipo" required>
<option value="">OS / Sindicato / Mutual</option>
<?php foreach(["Obra Social","Sindicato","Mutual"] as $op): ?>
<option value="<?= e($op) ?>" <?= (($editarPago["tipo"] ?? "") === $op) ? "selected" : "" ?>><?= e($op) ?></option>
<?php endforeach; ?>
</select>

<select name="forma_pago" required>
<option value="">Forma de pago</option>
<?php foreach(["Efectivo","Transferencia","Cheque"] as $op): ?>
<option value="<?= e($op) ?>" <?= (($editarPago["forma_pago"] ?? "") === $op) ? "selected" : "" ?>><?= e($op) ?></option>
<?php endforeach; ?>
</select>

<input type="number" step="0.01" min="0" name="monto" placeholder="Monto pagado" required value="<?= e($editarPago["monto"] ?? "") ?>">

<select name="pago_tipo">
<option value="Pago total" <?= (($editarPago["pago_tipo"] ?? "") === "Pago total") ? "selected" : "" ?>>Pago total</option>
<option value="Cuotas" <?= (($editarPago["pago_tipo"] ?? "") === "Cuotas") ? "selected" : "" ?>>Cuotas</option>
</select>

<input type="number" min="0" name="cuotas" placeholder="Cantidad de cuotas" value="<?= e($editarPago["cuotas"] ?? "") ?>">
<input type="text" name="periodo" class="periodo-input" placeholder="DD/MM/AA" maxlength="8" inputmode="numeric" pattern="(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/[0-9]{2}" required value="<?= e(periodoParaInput($editarPago["periodo"] ?? "")) ?>">
<input type="file" name="comprobante" accept=".pdf,.jpg,.jpeg,.png">
</div>

<?php if($errorPago !== ""): ?>
<p class="error"><?= e($errorPago) ?></p>
<?php endif; ?>

<?php if($editarPago && !empty($editarPago["comprobante"])): ?>
<p>
Comprobante actual:
<a class="btn-secundario" href="<?= e($editarPago["comprobante"]) ?>" target="_blank">👁️ Ver</a>
<a class="btn-secundario" href="<?= e($editarPago["comprobante"]) ?>" download>⬇️ Descargar</a>
<a class="btn-danger" href="?eliminar_comprobante=<?= e($editarPago["id"] ?? "") ?>" onclick="return confirm('¿Eliminar solo el comprobante de este pago?')">❌ Comprobante</a>
</p>
<?php endif; ?>

<textarea name="observaciones_pago" placeholder="Observaciones pago"><?= e($editarPago["observaciones"] ?? "") ?></textarea>

<br><br>
<button name="guardar_pago"><?= $editarPago ? "Guardar cambios pago" : "Guardar pago" ?></button>
<?php if($editarPago): ?>
<a class="btn-cancelar" href="index.php">Cancelar</a>
<?php endif; ?>
</form>
</div>
</div>
</section>

<section class="tab-panel" id="tab-cargar-acuerdo">
<div class="card collapsible-card" id="cargar-acuerdo" data-card="cargar-acuerdo">
<div class="card-header">
<h2>Cargar acuerdo</h2>
<button type="button" class="toggle-card">Minimizar</button>
</div>
<div class="card-body">

<form method="post" id="acuerdoForm">
<div class="grid">
<?php $empresaAcuerdoSeleccionada = buscarEmpresa($empresas, $acuerdoForm["empresa_id"] ?? ""); ?>
<div class="empresa-picker" data-hidden-name="acuerdo_empresa_id">
<input type="text" id="acuerdoEmpresaTexto" class="empresa-picker-input" placeholder="Buscar empresa por razón social o CUIT" autocomplete="off" value="<?= e($empresaAcuerdoSeleccionada ? (($empresaAcuerdoSeleccionada["razon"] ?? "") . " - " . ($empresaAcuerdoSeleccionada["cuit"] ?? "")) : "") ?>">
<input type="hidden" name="acuerdo_empresa_id" id="acuerdoEmpresa" class="empresa-picker-hidden" required value="<?= e($acuerdoForm["empresa_id"] ?? "") ?>">
<div class="empresa-picker-results"></div>
</div>

<select name="acuerdo_tipo" id="acuerdoTipo" required>
<option value="">Tipo</option>
<?php foreach(["Obra Social","Sindicato","Mutual"] as $op): ?>
<option value="<?= e($op) ?>" <?= (($acuerdoForm["tipo"] ?? "") === $op) ? "selected" : "" ?>><?= e($op) ?></option>
<?php endforeach; ?>
</select>

<input type="number" step="0.01" min="0" name="acuerdo_monto_total" placeholder="Monto total del acuerdo" value="<?= e($acuerdoForm["monto_total"] ?? "") ?>">
<input type="number" min="1" name="acuerdo_cantidad_cuotas" placeholder="Cantidad de cuotas" value="<?= e($acuerdoForm["cantidad_cuotas"] ?? "1") ?>">
<input type="number" step="0.01" min="0" name="acuerdo_monto_cuota" placeholder="Monto de cada cuota" value="<?= e($acuerdoForm["monto_cuota"] ?? "") ?>">
<input type="number" min="0" name="acuerdo_cuotas_pagadas_previas" placeholder="Cuotas ya pagadas" value="<?= e($acuerdoForm["cuotas_pagadas_previas"] ?? "0") ?>">
<input type="text" name="acuerdo_periodo_desde" class="periodo-input" placeholder="Período desde DD/MM/AA" maxlength="8" inputmode="numeric" pattern="(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/[0-9]{2}" value="<?= e(periodoParaInput($acuerdoForm["periodo_desde"] ?? "")) ?>">
<input type="text" name="acuerdo_periodo_hasta" class="periodo-input" placeholder="Período hasta DD/MM/AA" maxlength="8" inputmode="numeric" pattern="(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/[0-9]{2}" value="<?= e(periodoParaInput($acuerdoForm["periodo_hasta"] ?? "")) ?>">
</div>

<textarea name="acuerdo_observaciones" placeholder="Observaciones del acuerdo"><?= e($acuerdoForm["observaciones"] ?? "") ?></textarea>

<?php if($errorEmpresa !== "" && isset($_POST["guardar_acuerdo"])): ?>
<p class="error"><?= e($errorEmpresa) ?></p>
<?php endif; ?>

<br><br>
<button type="button" id="registrarCuotasPrevias">Registrar cuotas previas pagadas</button>
<button name="guardar_acuerdo">Guardar acuerdo</button>
</form>
</div>
</div>
</section>

<section class="tab-panel" id="tab-informe-periodo">
<div class="card collapsible-card" id="informe-periodo" data-card="informe-periodo">
<div class="card-header">
<h2>Informe por período</h2>
<button type="button" class="toggle-card">Minimizar</button>
</div>
<div class="card-body">

<div class="filters">
<div class="filters-grid informe">
<input type="text" id="informePeriodo" class="periodo-input" placeholder="DD/MM/AA" maxlength="8" inputmode="numeric">
<select id="informeTipo">
<option value="">Todos</option>
<option value="Obra Social">Obra Social</option>
<option value="Sindicato">Sindicato</option>
<option value="Mutual">Mutual</option>
</select>
<button type="button" id="generarInformePeriodo">Consultar</button>
</div>
</div>

<div class="informe-resumen">
<div class="box"><div class="label">Período consultado</div><div class="num" id="informePeriodoConsultado">--</div></div>
<div class="box"><div class="label">Total esperado del período</div><div class="num" id="informeEsperado">$0,00</div></div>
<div class="box"><div class="label">Total cobrado del período</div><div class="num" id="informeTotal">$0,00</div></div>
<div class="box"><div class="label">Pendiente de cobro</div><div class="num" id="informePendiente">$0,00</div></div>
<div class="box"><div class="label">Empresas que pagaron</div><div class="num" id="informePagaron">0</div></div>
<div class="box"><div class="label">Empresas que NO pagaron</div><div class="num" id="informeNoPagaron">0</div></div>
</div>

<h3 class="mini-title">Empresas que pagaron</h3>
<table>
<thead>
<tr>
<th>Razón social</th>
<th>CUIT</th>
<th>Tipo</th>
<th>Plan</th>
<th>Cuota esperada</th>
<th>Monto pagado</th>
<th>Estado</th>
<th>Fecha de pago</th>
<th>Comprobante</th>
<th>Acciones</th>
</tr>
</thead>
<tbody id="informePagaronBody">
<tr><td colspan="10" class="sin">Ingresá un período para consultar.</td></tr>
</tbody>
</table>

<h3 class="mini-title">Empresas que NO pagaron</h3>
<table>
<thead>
<tr>
<th>Razón social</th>
<th>CUIT</th>
<th>Tipo adeudado</th>
<th>Plan</th>
<th>Cuota esperada</th>
<th>Período acuerdo</th>
<th>Último pago registrado</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody id="informeNoPagaronBody">
<tr><td colspan="9" class="sin">Ingresá un período para consultar.</td></tr>
</tbody>
</table>
</div>
</div>
</section>

<section class="tab-panel" id="tab-buscar-empresa">
<div class="card collapsible-card" id="empresas" data-card="empresas">
<div class="card-header">
<h2>Buscar empresa</h2>
<button type="button" class="toggle-card">Minimizar</button>
</div>
<div class="card-body">

<div class="filters">
<input type="text" id="buscadorFichaEmpresa" placeholder="Buscar por razón social o CUIT">
<div id="resultadosFichaEmpresa" class="search-results"></div>
</div>
<div id="fichaEmpresa" class="empresa-ficha">
<span class="sin">Buscá y seleccioná una empresa para ver su ficha.</span>
</div>

<div class="filters">
<div class="filters-grid empresas">
<select id="filtroEmpresaCategoria">
<option value="">Todas</option>
<option value="os">Obra Social</option>
<option value="sindicato">Sindicato</option>
<option value="mutual">Mutual</option>
</select>
<select id="filtroEmpresaPlan">
<option value="">Plan: Todos</option>
<option value="pago-unico">Pago único</option>
<option value="acuerdo">Acuerdo</option>
</select>
<input type="text" id="filtroEmpresaTexto" placeholder="Buscar por razón social o CUIT">
<select id="filtroEmpresaEstado">
<option value="">Todas</option>
<option value="deuda">Con deuda</option>
<option value="cancelada">Canceladas</option>
</select>
<button type="button" id="limpiarFiltrosEmpresas">Limpiar filtros</button>
</div>
</div>

<table>
<thead>
<tr>
<th>Razón Social</th>
<th>CUIT</th>
<th>Monto total</th>
<th>Plan</th>
<th>Cuotas</th>
<th>Período acuerdo</th>
<th>Deuda OS</th>
<th>Cobrado OS</th>
<th>Saldo OS</th>
<th>Deuda Sindicato</th>
<th>Cobrado Sindicato</th>
<th>Saldo Sindicato</th>
<th>Deuda Mutual</th>
<th>Cobrado Mutual</th>
<th>Saldo Mutual</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php if(empty($empresas)): ?>
<tr><td colspan="16" class="sin">Todavía no hay empresas cargadas.</td></tr>
<?php endif; ?>

<?php foreach($empresas as $emp):
$deudaOS = floatval($emp["deuda_os"] ?? 0);
$deudaSind = floatval($emp["deuda_sindicato"] ?? 0);
$deudaMutual = floatval($emp["deuda_mutual"] ?? 0);
$acuerdoTabla = acuerdoDefault();
foreach (["Obra Social","Sindicato","Mutual"] as $tipoAcuerdoTabla) {
    $tmpAcuerdo = acuerdoEmpresa($emp, $tipoAcuerdoTabla);
    if (floatval($tmpAcuerdo["monto_total"] ?? 0) > 0 || floatval($tmpAcuerdo["monto_cuota"] ?? 0) > 0) {
        $acuerdoTabla = $tmpAcuerdo;
        break;
    }
}
$montoTotalEmpresa = floatval($acuerdoTabla["monto_total"] ?? 0);
$cantidadCuotasEmpresa = max(intval($acuerdoTabla["cantidad_cuotas"] ?? 1), 1);
$montoCuotaEmpresa = floatval($acuerdoTabla["monto_cuota"] ?? 0);
$periodoDesdeEmpresa = periodoParaInput($acuerdoTabla["periodo_desde"] ?? "");
$periodoHastaEmpresa = periodoParaInput($acuerdoTabla["periodo_hasta"] ?? "");
$esAcuerdoEmpresa = $cantidadCuotasEmpresa > 1;
$planEmpresa = $esAcuerdoEmpresa ? "Acuerdo" : "Pago único";
$planFiltroEmpresa = $esAcuerdoEmpresa ? "acuerdo" : "pago-unico";
$cuotasEmpresa = $esAcuerdoEmpresa ? ($cantidadCuotasEmpresa . " x " . dinero($montoCuotaEmpresa)) : "1";
$periodoAcuerdoEmpresa = $esAcuerdoEmpresa
    ? trim($periodoDesdeEmpresa . (($periodoDesdeEmpresa !== "" || $periodoHastaEmpresa !== "") ? " a " : "") . $periodoHastaEmpresa)
    : ($periodoDesdeEmpresa ?: $periodoHastaEmpresa);

$pagadoOS = totalPagado($pagos, $emp["id"], "Obra Social");
$pagadoSind = totalPagado($pagos, $emp["id"], "Sindicato");
$pagadoMutual = totalPagado($pagos, $emp["id"], "Mutual");

$saldoOS = max($deudaOS - $pagadoOS, 0);
$saldoSind = max($deudaSind - $pagadoSind, 0);
$saldoMutual = max($deudaMutual - $pagadoMutual, 0);

$tieneDeudaCargada = ($deudaOS > 0 || $deudaSind > 0 || $deudaMutual > 0);
$tieneSaldoReal = ($saldoOS > 0 || $saldoSind > 0 || $saldoMutual > 0);
$estadoEmpresa = $tieneSaldoReal ? "deuda" : ($tieneDeudaCargada ? "cancelada" : "");

$categoriaOS = ($deudaOS > 0 || $pagadoOS > 0) ? "1" : "0";
$categoriaSind = ($deudaSind > 0 || $pagadoSind > 0) ? "1" : "0";
$categoriaMutual = ($deudaMutual > 0 || $pagadoMutual > 0) ? "1" : "0";
?>
<tr class="fila-empresa" data-busqueda="<?= e(($emp["razon"] ?? "") . " " . ($emp["cuit"] ?? "")) ?>" data-estado="<?= e($estadoEmpresa) ?>" data-plan="<?= e($planFiltroEmpresa) ?>" data-os="<?= e($categoriaOS) ?>" data-sindicato="<?= e($categoriaSind) ?>" data-mutual="<?= e($categoriaMutual) ?>">
<td><?= e($emp["razon"]) ?></td>
<td><?= e($emp["cuit"]) ?></td>
<td><?= dinero($montoTotalEmpresa) ?></td>
<td><?= e($planEmpresa) ?></td>
<td><?= e($cuotasEmpresa) ?></td>
<td><?= e($periodoAcuerdoEmpresa) ?></td>
<td><?= dinero($deudaOS) ?></td>
<td class="saldo-ok"><?= dinero($pagadoOS) ?></td>
<td class="<?= $saldoOS <= 0 ? 'saldo-ok' : 'saldo-debe' ?>"><?= dinero($saldoOS) ?></td>
<td><?= dinero($deudaSind) ?></td>
<td class="saldo-ok"><?= dinero($pagadoSind) ?></td>
<td class="<?= $saldoSind <= 0 ? 'saldo-ok' : 'saldo-debe' ?>"><?= dinero($saldoSind) ?></td>
<td><?= dinero($deudaMutual) ?></td>
<td class="saldo-ok"><?= dinero($pagadoMutual) ?></td>
<td class="<?= $saldoMutual <= 0 ? 'saldo-ok' : 'saldo-debe' ?>"><?= dinero($saldoMutual) ?></td>
<td class="acciones">
<a href="?editar_empresa=<?= e($emp["id"]) ?>" title="Editar empresa">✏️</a>
<a href="?eliminar_empresa=<?= e($emp["id"]) ?>" onclick="return confirm('Esto elimina la empresa y todos sus pagos. ¿Seguro?')" title="Eliminar empresa">🗑️</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</section>

<section class="tab-panel" id="tab-pagos">
<div class="card collapsible-card" id="pagos" data-card="pagos">
<div class="card-header">
<h2>Pagos registrados</h2>
<button type="button" class="toggle-card">Minimizar</button>
</div>
<div class="card-body">

<div class="filters">
<div class="filters-grid">
<input type="text" id="filtroPagoTexto" placeholder="Buscar por empresa o CUIT">
<select id="filtroPagoTipo">
<option value="">Todos</option>
<option value="Obra Social">Obra Social</option>
<option value="Sindicato">Sindicato</option>
<option value="Mutual">Mutual</option>
</select>
<select id="filtroPagoForma">
<option value="">Todas</option>
<option value="Efectivo">Efectivo</option>
<option value="Transferencia">Transferencia</option>
<option value="Cheque">Cheque</option>
</select>
<input type="text" id="filtroPagoPeriodo" class="periodo-input" placeholder="DD/MM/AA" maxlength="8" inputmode="numeric">
<button type="button" id="limpiarFiltrosPagos">Limpiar filtros</button>
</div>
</div>

<table>
<thead>
<tr>
<th>Fecha</th>
<th>Empresa</th>
<th>CUIT</th>
<th>Tipo</th>
<th>Forma</th>
<th>Período</th>
<th>Monto</th>
<th>Cuotas</th>
<th>Comprobante</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php if(empty($pagos)): ?>
<tr><td colspan="10" class="sin">Todavía no hay pagos registrados.</td></tr>
<?php endif; ?>

<?php foreach(array_reverse($pagos) as $p):
$emp = buscarEmpresa($empresas, $p["empresa_id"] ?? "");
$periodoPago = periodoParaInput($p["periodo"] ?? "");
?>
<tr class="fila-pago" data-busqueda="<?= e(($emp["razon"] ?? "Empresa eliminada") . " " . ($emp["cuit"] ?? "")) ?>" data-tipo="<?= e($p["tipo"] ?? "") ?>" data-forma="<?= e($p["forma_pago"] ?? "") ?>" data-periodo="<?= e($periodoPago) ?>">
<td><?= e($p["fecha"] ?? "") ?></td>
<td><?= e($emp["razon"] ?? "Empresa eliminada") ?></td>
<td><?= e($emp["cuit"] ?? "") ?></td>
<td><span class="badge"><?= e($p["tipo"] ?? "") ?></span></td>
<td><?= e($p["forma_pago"] ?? "") ?></td>
<td><?= e($periodoPago) ?></td>
<td><?= dinero($p["monto"] ?? 0) ?></td>
<td><?= e(($p["pago_tipo"] ?? "") === "Cuotas" ? ($p["cuotas"] ?? "") : "Total") ?></td>
<td>
<?php if(!empty($p["comprobante"])): ?>
<a href="<?= e($p["comprobante"]) ?>" target="_blank">👁️</a>
<a href="<?= e($p["comprobante"]) ?>" download>⬇️</a>
<?php else: ?>
<span class="sin">Sin comprobante</span>
<?php endif; ?>
</td>
<td class="acciones">
<a href="?editar_pago=<?= e($p["id"]) ?>" title="Editar pago">✏️</a>
<a href="?eliminar_pago=<?= e($p["id"]) ?>" onclick="return confirm('¿Eliminar este pago?')" title="Eliminar pago">🗑️</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</section>

</main>
<script>
const empresasData = <?= json_encode($empresas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const pagosData = <?= json_encode($pagos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const tiposInforme = ["Obra Social", "Sindicato", "Mutual"];
const tabInicial = <?= json_encode($tabInicial, JSON_UNESCAPED_UNICODE) ?>;

function formatearPeriodo(valor) {
    const numeros = (valor || "").replace(/\D/g, "").slice(0, 6);
    if (numeros.length <= 2) return numeros;
    if (numeros.length <= 4) return numeros.slice(0, 2) + "/" + numeros.slice(2);
    return numeros.slice(0, 2) + "/" + numeros.slice(2, 4) + "/" + numeros.slice(4);
}

function periodoValidoCliente(valor) {
    const match = (valor || "").match(/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/(\d{2})$/);
    if (!match) return false;
    const dia = Number(match[1]);
    const mes = Number(match[2]);
    const anio = 2000 + Number(match[3]);
    const fecha = new Date(anio, mes - 1, dia);
    return fecha.getFullYear() === anio && fecha.getMonth() === mes - 1 && fecha.getDate() === dia;
}

document.querySelectorAll(".periodo-input").forEach((input) => {
    input.addEventListener("input", () => {
        input.value = formatearPeriodo(input.value);
    });
});

const empresaForm = document.getElementById("empresaForm");
if (empresaForm) {
    empresaForm.addEventListener("submit", (event) => {
        ["deuda_os", "deuda_sindicato", "deuda_mutual"].forEach((campo) => {
            const input = empresaForm.querySelector(`input[name="${campo}"]`);
            if (input && Number(input.value || 0) < 0) {
                event.preventDefault();
                alert("Las deudas no pueden ser negativas.");
                input.focus();
            }
        });
    });
}

const acuerdoFormEl = document.getElementById("acuerdoForm");
if (acuerdoFormEl) {
    acuerdoFormEl.addEventListener("submit", (event) => {
        const empresaId = acuerdoFormEl.querySelector('input[name="acuerdo_empresa_id"]')?.value || "";
        const montoTotal = Number(acuerdoFormEl.querySelector('input[name="acuerdo_monto_total"]')?.value || 0);
        const cantidadCuotas = Number(acuerdoFormEl.querySelector('input[name="acuerdo_cantidad_cuotas"]')?.value || 1);
        const montoCuota = Number(acuerdoFormEl.querySelector('input[name="acuerdo_monto_cuota"]')?.value || 0);
        const cuotasPrevias = Number(acuerdoFormEl.querySelector('input[name="acuerdo_cuotas_pagadas_previas"]')?.value || 0);
        const periodoDesde = acuerdoFormEl.querySelector('input[name="acuerdo_periodo_desde"]');
        const periodoHasta = acuerdoFormEl.querySelector('input[name="acuerdo_periodo_hasta"]');

        if (!empresaId) {
            event.preventDefault();
            alert("Seleccioná una empresa desde el buscador.");
            acuerdoFormEl.querySelector(".empresa-picker-input")?.focus();
            return;
        }

        if (montoTotal < 0) {
            event.preventDefault();
            alert("El monto total del acuerdo no puede ser negativo.");
            acuerdoFormEl.querySelector('input[name="acuerdo_monto_total"]')?.focus();
            return;
        }

        if (cantidadCuotas < 1) {
            event.preventDefault();
            alert("La cantidad de cuotas debe ser como mínimo 1.");
            acuerdoFormEl.querySelector('input[name="acuerdo_cantidad_cuotas"]')?.focus();
            return;
        }

        if (cuotasPrevias < 0 || cuotasPrevias > cantidadCuotas) {
            event.preventDefault();
            alert("Las cuotas ya pagadas deben estar entre 0 y la cantidad total de cuotas.");
            acuerdoFormEl.querySelector('input[name="acuerdo_cuotas_pagadas_previas"]')?.focus();
            return;
        }

        if (cantidadCuotas > 1 && montoCuota <= 0) {
            event.preventDefault();
            alert("Si hay acuerdo, el monto de cada cuota debe ser mayor a 0.");
            acuerdoFormEl.querySelector('input[name="acuerdo_monto_cuota"]')?.focus();
            return;
        }

        if (cantidadCuotas > 1 && (!periodoDesde?.value || !periodoHasta?.value)) {
            event.preventDefault();
            alert("Si hay acuerdo, el período desde y el período hasta son obligatorios.");
            (periodoDesde && !periodoDesde.value ? periodoDesde : periodoHasta)?.focus();
            return;
        }

        if (periodoDesde?.value && !periodoValidoCliente(periodoDesde.value)) {
            event.preventDefault();
            alert("El período desde debe tener formato DD/MM/AA.");
            periodoDesde.focus();
            return;
        }

        if (periodoHasta?.value && !periodoValidoCliente(periodoHasta.value)) {
            event.preventDefault();
            alert("El período hasta debe tener formato DD/MM/AA.");
            periodoHasta.focus();
        }
    });
}

const registrarCuotasPrevias = document.getElementById("registrarCuotasPrevias");
if (registrarCuotasPrevias && acuerdoFormEl) {
    registrarCuotasPrevias.addEventListener("click", () => {
        const cantidad = Number(acuerdoFormEl.querySelector('input[name="acuerdo_cantidad_cuotas"]')?.value || 1);
        const previas = Number(acuerdoFormEl.querySelector('input[name="acuerdo_cuotas_pagadas_previas"]')?.value || 0);
        if (previas < 0 || previas > cantidad) {
            alert("Las cuotas ya pagadas deben estar entre 0 y la cantidad total de cuotas.");
            acuerdoFormEl.querySelector('input[name="acuerdo_cuotas_pagadas_previas"]')?.focus();
            return;
        }
        alert(`Se registrarán ${previas} cuotas previas pagadas al guardar el acuerdo. No se crearán pagos reales.`);
    });
}

const pagoForm = document.querySelector('form[enctype="multipart/form-data"]');
if (pagoForm) {
    pagoForm.addEventListener("submit", (event) => {
        const empresaId = pagoForm.querySelector('input[name="empresa_id"]')?.value || "";
        const periodo = pagoForm.querySelector('input[name="periodo"]');
        if (!empresaId) {
            event.preventDefault();
            alert("Seleccioná una empresa desde el buscador.");
            pagoForm.querySelector(".empresa-picker-input")?.focus();
            return;
        }
        if (periodo && !periodoValidoCliente(periodo.value)) {
            event.preventDefault();
            alert("El periodo debe tener formato DD/MM/AA.");
            periodo.focus();
        }
    });
}

function textoNormalizado(valor) {
    return (valor || "").toString().toLowerCase();
}

function escapeHtml(valor) {
    return (valor ?? "").toString().replace(/[&<>"']/g, (char) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
    }[char]));
}

function dineroCliente(valor) {
    const numero = Math.max(Number(valor) || 0, 0);
    return "$" + numero.toLocaleString("es-AR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function periodoNormalizado(valor) {
    const periodo = (valor || "").toString().trim();
    if (/^\d{4}-\d{2}$/.test(periodo)) {
        return "01/" + periodo.slice(5, 7) + "/" + periodo.slice(2, 4);
    }
    if (/^\d{2}\/\d{2}$/.test(periodo)) {
        return "01/" + periodo;
    }
    return periodo;
}

function periodoAIndice(periodo) {
    const normalizado = periodoNormalizado(periodo);
    if (!periodoValidoCliente(normalizado)) return null;
    const [dia, mes, anio] = normalizado.split("/").map(Number);
    return (2000 + anio) * 372 + mes * 31 + dia;
}

function periodoAMesIndice(periodo) {
    const normalizado = periodoNormalizado(periodo);
    if (!periodoValidoCliente(normalizado)) return null;
    const [, mes, anio] = normalizado.split("/").map(Number);
    return (2000 + anio) * 12 + mes;
}

function acuerdoEmpresaTipo(empresa, tipo) {
    const base = {
        monto_total: 0,
        cantidad_cuotas: 1,
        monto_cuota: 0,
        cuotas_pagadas_previas: 0,
        periodo_desde: "",
        periodo_hasta: "",
        observaciones: ""
    };
    if (empresa?.acuerdos && empresa.acuerdos[tipo]) {
        return { ...base, ...empresa.acuerdos[tipo] };
    }
    return {
        ...base,
        monto_total: empresa?.monto_total || 0,
        cantidad_cuotas: empresa?.cantidad_cuotas || 1,
        monto_cuota: empresa?.monto_cuota || 0,
        cuotas_pagadas_previas: empresa?.cuotas_pagadas_previas || 0,
        periodo_desde: empresa?.periodo_desde || "",
        periodo_hasta: empresa?.periodo_hasta || "",
        observaciones: empresa?.observaciones_acuerdo || ""
    };
}

function planEmpresa(empresa, tipo = "Obra Social") {
    const acuerdo = acuerdoEmpresaTipo(empresa, tipo);
    const cantidad = Math.max(Number(acuerdo.cantidad_cuotas || 1), 1);
    return cantidad > 1 ? "Acuerdo" : "Pago único";
}

function periodoAcuerdoEmpresa(empresa, tipo = "Obra Social") {
    const acuerdo = acuerdoEmpresaTipo(empresa, tipo);
    const desde = periodoNormalizado(acuerdo.periodo_desde || "");
    const hasta = periodoNormalizado(acuerdo.periodo_hasta || "");
    if (desde && hasta && desde !== hasta) return `${desde} a ${hasta}`;
    return desde || hasta || "";
}

function tieneDatosAcuerdo(empresa, tipo = "Obra Social") {
    const acuerdo = acuerdoEmpresaTipo(empresa, tipo);
    return Number(acuerdo.monto_total || 0) > 0 ||
        Number(acuerdo.monto_cuota || 0) > 0 ||
        Number(acuerdo.cuotas_pagadas_previas || 0) > 0 ||
        Number(acuerdo.cantidad_cuotas || 0) > 0 ||
        !!periodoNormalizado(acuerdo.periodo_desde || "") ||
        !!periodoNormalizado(acuerdo.periodo_hasta || "");
}

function cuotaEsperadaEmpresaPeriodo(empresa, periodo, tipo = "Obra Social") {
    const acuerdo = acuerdoEmpresaTipo(empresa, tipo);
    const cantidad = Math.max(Number(acuerdo.cantidad_cuotas || 1), 1);
    const consultado = periodoAMesIndice(periodo);
    const desde = periodoAMesIndice(acuerdo.periodo_desde || "");
    const hasta = periodoAMesIndice(acuerdo.periodo_hasta || "");
    if (consultado === null) return 0;

    if (cantidad <= 1) {
        const periodoUnico = desde ?? hasta;
        if (periodoUnico !== null && consultado === periodoUnico) {
            return Math.max(Number(acuerdo.monto_total || acuerdo.monto_cuota || 0), 0);
        }
        return 0;
    }

    if (desde !== null && hasta !== null && consultado >= desde && consultado <= hasta) {
        return Math.max(Number(acuerdo.monto_cuota || 0), 0);
    }

    return 0;
}

function numeroCuotaEmpresaPeriodo(empresa, periodo, tipo = "Obra Social") {
    const acuerdo = acuerdoEmpresaTipo(empresa, tipo);
    const cantidad = Math.max(Number(acuerdo.cantidad_cuotas || 1), 1);
    const consultado = periodoAMesIndice(periodo);
    const desde = periodoAMesIndice(acuerdo.periodo_desde || "");
    if (consultado === null || desde === null) return 0;
    const numero = consultado - desde + 1;
    return numero >= 1 && numero <= cantidad ? numero : 0;
}

function cuotaPreviaPagadaEmpresaPeriodo(empresa, periodo, tipo = "Obra Social") {
    const acuerdo = acuerdoEmpresaTipo(empresa, tipo);
    const previas = Math.max(Number(acuerdo.cuotas_pagadas_previas || 0), 0);
    const numero = numeroCuotaEmpresaPeriodo(empresa, periodo, tipo);
    return numero > 0 && numero <= previas;
}

function obtenerEmpresa(id) {
    return empresasData.find((empresa) => (empresa.id || "") === (id || "")) || null;
}

function etiquetaEmpresa(empresa) {
    return empresa ? `${empresa.razon || ""} - ${empresa.cuit || ""}`.trim() : "";
}

function activarTab(tabId) {
    document.querySelectorAll(".tab-btn").forEach((btn) => {
        btn.classList.toggle("active", btn.dataset.tab === tabId);
    });
    document.querySelectorAll(".tab-panel").forEach((panel) => {
        panel.classList.toggle("active", panel.id === "tab-" + tabId);
    });
}

function actualizarBotonCard(card) {
    const boton = card.querySelector(".toggle-card");
    if (!boton) return;
    boton.textContent = card.classList.contains("is-collapsed") ? "Mostrar" : "Minimizar";
}

function abrirCard(card) {
    if (!card) return;
    card.classList.remove("is-collapsed");
    actualizarBotonCard(card);
}

function configurarCardsPlegables() {
    document.querySelectorAll(".collapsible-card").forEach((card) => {
        const boton = card.querySelector(".toggle-card");
        actualizarBotonCard(card);

        if (boton) {
            boton.addEventListener("click", () => {
                card.classList.toggle("is-collapsed");
                actualizarBotonCard(card);
            });
        }
    });

}

function seleccionarEmpresaPicker(hiddenName, empresaId) {
    const picker = document.querySelector(`.empresa-picker[data-hidden-name="${hiddenName}"]`);
    const empresa = obtenerEmpresa(empresaId);
    if (!picker || !empresa) return;

    const visible = picker.querySelector(".empresa-picker-input");
    const hidden = picker.querySelector(".empresa-picker-hidden");
    const resultados = picker.querySelector(".empresa-picker-results");
    if (visible) visible.value = etiquetaEmpresa(empresa);
    if (hidden) hidden.value = empresa.id || "";
    if (resultados) {
        resultados.innerHTML = "";
        resultados.classList.remove("active");
    }
}

function configurarEmpresaPickers() {
    document.querySelectorAll(".empresa-picker").forEach((picker) => {
        const visible = picker.querySelector(".empresa-picker-input");
        const hidden = picker.querySelector(".empresa-picker-hidden");
        const resultados = picker.querySelector(".empresa-picker-results");
        if (!visible || !hidden || !resultados) return;

        visible.addEventListener("input", () => {
            hidden.value = "";
            const texto = textoNormalizado(visible.value);
            if (texto.length < 2) {
                resultados.innerHTML = "";
                resultados.classList.remove("active");
                return;
            }

            const coincidencias = empresasData
                .filter((empresa) => textoNormalizado((empresa.razon || "") + " " + (empresa.cuit || "")).includes(texto))
                .slice(0, 30);

            resultados.innerHTML = coincidencias.length
                ? coincidencias.map((empresa) => `<div class="empresa-picker-option" data-id="${escapeHtml(empresa.id || "")}">${escapeHtml(etiquetaEmpresa(empresa))}</div>`).join("")
                : '<div class="empresa-picker-option sin">Sin coincidencias</div>';
            resultados.classList.add("active");

            resultados.querySelectorAll(".empresa-picker-option[data-id]").forEach((opcion) => {
                opcion.addEventListener("click", () => {
                    seleccionarEmpresaPicker(hidden.name, opcion.dataset.id);
                    if (hidden.name === "acuerdo_empresa_id") cargarAcuerdoExistente();
                });
            });
        });

        visible.addEventListener("focus", () => {
            if (visible.value.length >= 2 && resultados.innerHTML) resultados.classList.add("active");
        });
    });

    document.addEventListener("click", (event) => {
        if (event.target.closest(".empresa-picker")) return;
        document.querySelectorAll(".empresa-picker-results").forEach((resultados) => resultados.classList.remove("active"));
    });
}

function configurarTabs() {
    document.querySelectorAll(".tab-btn, .tab-jump").forEach((boton) => {
        boton.addEventListener("click", () => activarTab(boton.dataset.tab));
    });
    const hashTab = window.location.hash ? window.location.hash.replace("#", "") : "";
    activarTab(hashTab && document.getElementById("tab-" + hashTab) ? hashTab : tabInicial);
}

function configurarFiltrosEmpresas() {
    const categoria = document.getElementById("filtroEmpresaCategoria");
    const plan = document.getElementById("filtroEmpresaPlan");
    const texto = document.getElementById("filtroEmpresaTexto");
    const estado = document.getElementById("filtroEmpresaEstado");
    const limpiar = document.getElementById("limpiarFiltrosEmpresas");
    const filas = Array.from(document.querySelectorAll(".fila-empresa"));
    if (!categoria || !plan || !texto || !estado || !limpiar) return;

    const aplicar = () => {
        const categoriaValor = categoria.value;
        const planValor = plan.value;
        const busqueda = textoNormalizado(texto.value);
        const estadoValor = estado.value;

        filas.forEach((fila) => {
            const coincideCategoria = !categoriaValor || fila.dataset[categoriaValor] === "1";
            const coincidePlan = !planValor || fila.dataset.plan === planValor;
            const coincideTexto = textoNormalizado(fila.dataset.busqueda).includes(busqueda);
            const coincideEstado = !estadoValor || fila.dataset.estado === estadoValor;
            fila.classList.toggle("fila-oculta", !(coincideCategoria && coincidePlan && coincideTexto && coincideEstado));
        });
    };

    categoria.addEventListener("change", aplicar);
    plan.addEventListener("change", aplicar);
    texto.addEventListener("input", aplicar);
    estado.addEventListener("change", aplicar);
    limpiar.addEventListener("click", () => {
        categoria.value = "";
        plan.value = "";
        texto.value = "";
        estado.value = "";
        aplicar();
    });
}

function configurarFiltrosPagos() {
    const texto = document.getElementById("filtroPagoTexto");
    const tipo = document.getElementById("filtroPagoTipo");
    const forma = document.getElementById("filtroPagoForma");
    const periodo = document.getElementById("filtroPagoPeriodo");
    const limpiar = document.getElementById("limpiarFiltrosPagos");
    const filas = Array.from(document.querySelectorAll(".fila-pago"));
    if (!texto || !tipo || !forma || !periodo || !limpiar) return;

    const aplicar = () => {
        const busqueda = textoNormalizado(texto.value);
        const periodoValor = periodo.value;

        filas.forEach((fila) => {
            const coincideTexto = textoNormalizado(fila.dataset.busqueda).includes(busqueda);
            const coincideTipo = !tipo.value || fila.dataset.tipo === tipo.value;
            const coincideForma = !forma.value || fila.dataset.forma === forma.value;
            const coincidePeriodo = !periodoValor || (fila.dataset.periodo || "").startsWith(periodoValor);
            fila.classList.toggle("fila-oculta", !(coincideTexto && coincideTipo && coincideForma && coincidePeriodo));
        });
    };

    texto.addEventListener("input", aplicar);
    tipo.addEventListener("change", aplicar);
    forma.addEventListener("change", aplicar);
    periodo.addEventListener("input", aplicar);
    limpiar.addEventListener("click", () => {
        texto.value = "";
        tipo.value = "";
        forma.value = "";
        periodo.value = "";
        aplicar();
    });
}

function empresaObligadaPorTipo(empresa, tipo) {
    const deudaCampo = {
        "Obra Social": "deuda_os",
        "Sindicato": "deuda_sindicato",
        "Mutual": "deuda_mutual"
    }[tipo];

    const tieneDeuda = Number(empresa[deudaCampo] || 0) > 0;
    const tienePagoHistorico = pagosData.some((pago) =>
        (pago.empresa_id || "") === (empresa.id || "") && (pago.tipo || "") === tipo
    );

    return tieneDeuda || tienePagoHistorico || tieneDatosAcuerdo(empresa, tipo);
}

function totalPagadoCliente(empresaId, tipo) {
    return pagosData.reduce((total, pago) => {
        if ((pago.empresa_id || "") === empresaId && (pago.tipo || "") === tipo) {
            return total + (Number(pago.monto) || 0);
        }
        return total;
    }, 0);
}

function renderAcuerdoResumen(empresa, tipo) {
    const acuerdo = acuerdoEmpresaTipo(empresa, tipo);
    if (!tieneDatosAcuerdo(empresa, tipo) || (Number(acuerdo.monto_total || 0) <= 0 && Number(acuerdo.monto_cuota || 0) <= 0)) {
        return `${tipo}: Sin acuerdo cargado`;
    }

    const cuotas = Math.max(Number(acuerdo.cantidad_cuotas || 1), 1);
    const previas = Math.min(Math.max(Number(acuerdo.cuotas_pagadas_previas || 0), 0), cuotas);
    const plan = cuotas > 1 ? "Acuerdo" : "Pago único";
    const detalle = cuotas > 1 ? `${cuotas} x ${dineroCliente(acuerdo.monto_cuota)}` : dineroCliente(acuerdo.monto_total || acuerdo.monto_cuota);
    const periodo = periodoAcuerdoEmpresa(empresa, tipo);
    return `${tipo}: ${plan} ${detalle}${previas ? " - previas pagadas: " + previas : ""}${periodo ? " (" + periodo + ")" : ""}`;
}

function resumenDetalleAcuerdo(empresa, tipo) {
    const acuerdo = acuerdoEmpresaTipo(empresa, tipo);
    if (!tieneDatosAcuerdo(empresa, tipo) || (Number(acuerdo.monto_total || 0) <= 0 && Number(acuerdo.monto_cuota || 0) <= 0)) {
        return `<div class="box"><div class="label">${escapeHtml(tipo)}</div><div class="sin">Sin acuerdo cargado</div></div>`;
    }

    const cantidad = Math.max(Number(acuerdo.cantidad_cuotas || 1), 1);
    const previas = Math.min(Math.max(Number(acuerdo.cuotas_pagadas_previas || 0), 0), cantidad);
    const montoCuota = cantidad > 1 ? Number(acuerdo.monto_cuota || 0) : Number(acuerdo.monto_total || acuerdo.monto_cuota || 0);
    const pagosSistema = pagosData.filter((pago) => (pago.empresa_id || "") === (empresa.id || "") && (pago.tipo || "") === tipo);
    const cuotasSistema = pagosSistema.length;
    const totalSistema = pagosSistema.reduce((total, pago) => total + (Number(pago.monto) || 0), 0);
    const cuotasPendientes = Math.max(cantidad - previas - cuotasSistema, 0);
    const saldoEstimado = Math.max((cantidad - previas) * montoCuota - totalSistema, 0);

    return `<div class="box">
<div class="label">${escapeHtml(tipo)}</div>
<div>Monto total acuerdo: ${dineroCliente(acuerdo.monto_total)}</div>
<div>Cantidad total de cuotas: ${cantidad}</div>
<div>Monto cuota: ${dineroCliente(montoCuota)}</div>
<div>Cuotas previas pagadas: ${previas}</div>
<div>Cuotas registradas en sistema: ${cuotasSistema}</div>
<div>Cuotas pendientes: ${cuotasPendientes}</div>
<div>Saldo pendiente estimado: ${dineroCliente(saldoEstimado)}</div>
</div>`;
}

function seleccionarEmpresaFicha(empresaId) {
    const empresa = obtenerEmpresa(empresaId);
    const ficha = document.getElementById("fichaEmpresa");
    if (!empresa || !ficha) return;

    const saldos = tiposInforme.map((tipo) => {
        const deudaCampo = {"Obra Social":"deuda_os","Sindicato":"deuda_sindicato","Mutual":"deuda_mutual"}[tipo];
        const deuda = Number(empresa[deudaCampo] || 0);
        const cobrado = totalPagadoCliente(empresa.id || "", tipo);
        const saldo = Math.max(deuda - cobrado, 0);
        return { tipo, deuda, cobrado, saldo };
    });

    const pagosEmpresa = pagosData.filter((pago) => (pago.empresa_id || "") === (empresa.id || ""));
    ficha.innerHTML = `
<h3>${escapeHtml(empresa.razon || "")}</h3>
<p><strong>CUIT:</strong> ${escapeHtml(empresa.cuit || "")}</p>
<div class="empresa-ficha-grid">
${saldos.map((s) => `<div class="box"><div class="label">${escapeHtml(s.tipo)}</div><div>Deuda: ${dineroCliente(s.deuda)}</div><div>Cobrado: ${dineroCliente(s.cobrado)}</div><div>Saldo: ${dineroCliente(s.saldo)}</div></div>`).join("")}
</div>
<h3 class="mini-title">Acuerdos vigentes</h3>
<p>${tiposInforme.map((tipo) => escapeHtml(renderAcuerdoResumen(empresa, tipo))).join("<br>")}</p>
<div class="empresa-ficha-grid">${tiposInforme.map((tipo) => resumenDetalleAcuerdo(empresa, tipo)).join("")}</div>
<h3 class="mini-title">Pagos registrados</h3>
${pagosEmpresa.length ? `<table><thead><tr><th>Fecha</th><th>Tipo</th><th>Período</th><th>Monto</th><th>Forma</th><th>Acciones</th></tr></thead><tbody>${pagosEmpresa.map((pago) => `<tr><td>${escapeHtml(pago.fecha || "")}</td><td>${escapeHtml(pago.tipo || "")}</td><td>${escapeHtml(periodoNormalizado(pago.periodo || ""))}</td><td>${dineroCliente(pago.monto)}</td><td>${escapeHtml(pago.forma_pago || "")}</td><td><a href="?eliminar_pago=${encodeURIComponent(pago.id || "")}" onclick="return confirm('¿Eliminar este pago?')" title="Eliminar pago">🗑️</a></td></tr>`).join("")}</tbody></table>` : '<p class="sin">Sin pagos registrados.</p>'}
<br>
<a class="btn-secundario" href="?editar_empresa=${encodeURIComponent(empresa.id || "")}">Editar empresa</a>
<a class="btn-danger" href="?eliminar_empresa=${encodeURIComponent(empresa.id || "")}" onclick="return confirm('Esto elimina la empresa y todos sus pagos asociados. ¿Seguro?')">🗑️ Eliminar empresa</a>
<button type="button" class="btn-small ficha-cargar-pago" data-empresa="${escapeHtml(empresa.id || "")}">Cargar pago</button>
<button type="button" class="btn-small ficha-cargar-acuerdo" data-empresa="${escapeHtml(empresa.id || "")}">Cargar acuerdo</button>
`;

    ficha.querySelector(".ficha-cargar-pago")?.addEventListener("click", () => completarFormularioPago(empresa.id || "", "", ""));
    ficha.querySelector(".ficha-cargar-acuerdo")?.addEventListener("click", () => completarFormularioAcuerdo(empresa.id || "", ""));
}

function renderResultadosEmpresa(input, contenedor, limite = 12) {
    const texto = textoNormalizado(input.value);
    if (!texto) {
        contenedor.innerHTML = "";
        return;
    }

    const resultados = empresasData
        .filter((empresa) => textoNormalizado((empresa.razon || "") + " " + (empresa.cuit || "")).includes(texto))
        .slice(0, limite);

    contenedor.innerHTML = resultados.length
        ? resultados.map((empresa) => `<div class="search-result"><span>${escapeHtml(empresa.razon || "")} - ${escapeHtml(empresa.cuit || "")}</span><button type="button" data-empresa="${escapeHtml(empresa.id || "")}">Ver ficha</button></div>`).join("")
        : '<p class="sin">Sin coincidencias.</p>';

    contenedor.querySelectorAll("button[data-empresa]").forEach((boton) => {
        boton.addEventListener("click", () => {
            activarTab("buscar-empresa");
            seleccionarEmpresaFicha(boton.dataset.empresa);
        });
    });
}

function ultimoPagoEmpresaTipo(empresaId, tipo) {
    const pagosTipo = pagosData
        .filter((pago) => (pago.empresa_id || "") === empresaId && (pago.tipo || "") === tipo)
        .sort((a, b) => {
            const fechaA = (a.fecha || a.fecha_carga || "").toString();
            const fechaB = (b.fecha || b.fecha_carga || "").toString();
            return fechaB.localeCompare(fechaA);
        });

    return pagosTipo[0] || null;
}

function completarFormularioPago(empresaId, tipo, periodo) {
    activarTab("cargar-pago");
    const card = document.getElementById("cargar-pago");
    abrirCard(card);

    const form = document.querySelector('form[enctype="multipart/form-data"]');
    if (form) {
        const pagoId = form.querySelector('input[name="pago_id"]');
        const comprobanteActual = form.querySelector('input[name="comprobante_actual"]');
        const tipoInput = form.querySelector('select[name="tipo"]');
        const periodoInput = form.querySelector('input[name="periodo"]');
        const monto = form.querySelector('input[name="monto"]');
        const titulo = card ? card.querySelector("h2") : null;
        const guardar = form.querySelector('button[name="guardar_pago"]');

        if (pagoId) pagoId.value = "";
        if (comprobanteActual) comprobanteActual.value = "";
        seleccionarEmpresaPicker("empresa_id", empresaId);
        if (tipoInput) tipoInput.value = tipo;
        if (periodoInput) periodoInput.value = periodo;
        if (titulo) titulo.textContent = "Cargar pago";
        if (guardar) guardar.textContent = "Guardar pago";
        if (monto) monto.focus();
    }

    window.scrollTo({ top: 0, behavior: "smooth" });
}

function completarFormularioAcuerdo(empresaId, tipo) {
    activarTab("cargar-acuerdo");
    const card = document.getElementById("cargar-acuerdo");
    abrirCard(card);

    const form = document.getElementById("acuerdoForm");
    if (form) {
        const tipoInput = form.querySelector('select[name="acuerdo_tipo"]');
        seleccionarEmpresaPicker("acuerdo_empresa_id", empresaId);
        if (tipoInput && tipo) tipoInput.value = tipo;
        cargarAcuerdoExistente();
    }

    window.scrollTo({ top: 0, behavior: "smooth" });
}

function cargarAcuerdoExistente() {
    const form = document.getElementById("acuerdoForm");
    if (!form) return;
    const empresaId = form.querySelector('input[name="acuerdo_empresa_id"]')?.value || "";
    const tipo = form.querySelector('select[name="acuerdo_tipo"]')?.value || "";
    const empresa = obtenerEmpresa(empresaId);
    if (!empresa || !tipo) return;

    const acuerdo = acuerdoEmpresaTipo(empresa, tipo);
    form.querySelector('input[name="acuerdo_monto_total"]').value = acuerdo.monto_total || "";
    form.querySelector('input[name="acuerdo_cantidad_cuotas"]').value = acuerdo.cantidad_cuotas || "1";
    form.querySelector('input[name="acuerdo_monto_cuota"]').value = acuerdo.monto_cuota || "";
    form.querySelector('input[name="acuerdo_cuotas_pagadas_previas"]').value = acuerdo.cuotas_pagadas_previas || "0";
    form.querySelector('input[name="acuerdo_periodo_desde"]').value = periodoNormalizado(acuerdo.periodo_desde || "");
    form.querySelector('input[name="acuerdo_periodo_hasta"]').value = periodoNormalizado(acuerdo.periodo_hasta || "");
    form.querySelector('textarea[name="acuerdo_observaciones"]').value = acuerdo.observaciones || "";
}

function configurarInformePeriodo() {
    const periodoInput = document.getElementById("informePeriodo");
    const tipoInput = document.getElementById("informeTipo");
    const consultar = document.getElementById("generarInformePeriodo");
    const esperadoEl = document.getElementById("informeEsperado");
    const totalEl = document.getElementById("informeTotal");
    const pendienteEl = document.getElementById("informePendiente");
    const pagaronEl = document.getElementById("informePagaron");
    const noPagaronEl = document.getElementById("informeNoPagaron");
    const periodoEl = document.getElementById("informePeriodoConsultado");
    const pagaronBody = document.getElementById("informePagaronBody");
    const noPagaronBody = document.getElementById("informeNoPagaronBody");

    if (!periodoInput || !tipoInput || !consultar || !esperadoEl || !totalEl || !pendienteEl || !pagaronEl || !noPagaronEl || !periodoEl || !pagaronBody || !noPagaronBody) return;

    const render = () => {
        const periodo = periodoInput.value;
        const tiposSeleccionados = tipoInput.value ? [tipoInput.value] : tiposInforme;

        if (!periodoValidoCliente(periodo)) {
            esperadoEl.textContent = "$0,00";
            totalEl.textContent = "$0,00";
            pendienteEl.textContent = "$0,00";
            pagaronEl.textContent = "0";
            noPagaronEl.textContent = "0";
            periodoEl.textContent = "--";
            pagaronBody.innerHTML = '<tr><td colspan="10" class="sin">Ingresá un período DD/MM/AA para consultar.</td></tr>';
            noPagaronBody.innerHTML = '<tr><td colspan="9" class="sin">Ingresá un período DD/MM/AA para consultar.</td></tr>';
            return;
        }

        const pagosPeriodo = pagosData.filter((pago) =>
            periodoNormalizado(pago.periodo) === periodo && tiposSeleccionados.includes(pago.tipo || "")
        );

        const pagosAgrupados = new Map();
        pagosPeriodo.forEach((pago) => {
            const clave = (pago.empresa_id || "") + "|" + (pago.tipo || "");
            const actual = pagosAgrupados.get(clave) || {
                empresaId: pago.empresa_id || "",
                tipo: pago.tipo || "",
                monto: 0,
                fechas: [],
                comprobantes: [],
                ids: []
            };
            actual.monto += Number(pago.monto) || 0;
            if (pago.fecha) actual.fechas.push(pago.fecha);
            if (pago.comprobante) actual.comprobantes.push(pago.comprobante);
            if (pago.id) actual.ids.push(pago.id);
            pagosAgrupados.set(clave, actual);
        });

        const filasPagaron = [];
        const deudores = [];
        let totalEsperado = 0;
        let totalCubiertoPrevio = 0;

        empresasData.forEach((empresa) => {
            tiposSeleccionados.forEach((tipo) => {
                if (!empresaObligadaPorTipo(empresa, tipo)) return;

                const clave = (empresa.id || "") + "|" + tipo;
                const esperadoPorAcuerdo = cuotaEsperadaEmpresaPeriodo(empresa, periodo, tipo);
                const usaAcuerdo = tieneDatosAcuerdo(empresa, tipo);
                const pagadaPrevia = cuotaPreviaPagadaEmpresaPeriodo(empresa, periodo, tipo);
                const pago = pagosAgrupados.get(clave);
                const aplicaEnPeriodo = esperadoPorAcuerdo > 0 || !usaAcuerdo || pago;

                if (!aplicaEnPeriodo) return;

                const esperado = esperadoPorAcuerdo;
                totalEsperado += esperado;

                if (pagadaPrevia && !pago) {
                    totalCubiertoPrevio += esperado;
                    filasPagaron.push({
                        empresa,
                        tipo,
                        plan: planEmpresa(empresa, tipo),
                        esperado,
                        pagado: esperado,
                        fechas: ["Cuota previa"],
                        comprobantes: [],
                        ids: [],
                        estado: "PAGADA PREVIA"
                    });
                } else if (pago) {
                    filasPagaron.push({
                        empresa,
                        tipo,
                        plan: planEmpresa(empresa, tipo),
                        esperado,
                        pagado: pago.monto,
                        fechas: pago.fechas,
                        comprobantes: pago.comprobantes,
                        ids: pago.ids,
                        estado: esperado <= 0 ? "EXTRA" : (pago.monto >= esperado ? "AL DÍA" : "PARCIAL")
                    });
                } else {
                    deudores.push({ empresa, tipo, plan: planEmpresa(empresa, tipo), esperado });
                }
            });
        });

        pagosAgrupados.forEach((pago, clave) => {
            if (filasPagaron.some((fila) => (fila.empresa.id || "") + "|" + fila.tipo === clave)) return;
            const empresa = obtenerEmpresa(pago.empresaId);
            filasPagaron.push({
                empresa,
                tipo: pago.tipo,
                plan: empresa ? planEmpresa(empresa, pago.tipo) : "",
                esperado: empresa ? cuotaEsperadaEmpresaPeriodo(empresa, periodo, pago.tipo) : 0,
                pagado: pago.monto,
                fechas: pago.fechas,
                comprobantes: pago.comprobantes,
                ids: pago.ids,
                estado: empresa && cuotaEsperadaEmpresaPeriodo(empresa, periodo, pago.tipo) > 0 ? "AL DÍA" : "EXTRA"
            });
        });

        const totalPagado = filasPagaron
            .filter((fila) => fila.estado !== "PAGADA PREVIA")
            .reduce((total, fila) => total + fila.pagado, 0);
        const pendiente = Math.max(totalEsperado - totalPagado - totalCubiertoPrevio, 0);
        const empresasQuePagaron = new Set(filasPagaron.map((fila) => fila.empresa?.id || "").filter(Boolean));
        const empresasQueNoPagaron = new Set(deudores.map((fila) => fila.empresa.id || "").filter(Boolean));

        esperadoEl.textContent = dineroCliente(totalEsperado);
        totalEl.textContent = dineroCliente(totalPagado);
        pendienteEl.textContent = dineroCliente(pendiente);
        pagaronEl.textContent = empresasQuePagaron.size.toString();
        noPagaronEl.textContent = empresasQueNoPagaron.size.toString();
        periodoEl.textContent = periodo;

        if (filasPagaron.length === 0) {
            pagaronBody.innerHTML = '<tr><td colspan="10" class="sin">No hay pagos registrados para este período y tipo.</td></tr>';
        } else {
            pagaronBody.innerHTML = filasPagaron.map((fila) => {
                const estadoClase = fila.estado === "AL DÍA" ? "estado-ok" : (fila.estado === "PAGADA PREVIA" ? "estado-previa" : (fila.estado === "EXTRA" ? "estado-ok" : "estado-parcial"));
                const comprobantes = (fila.comprobantes || []).length
                    ? fila.comprobantes.map((comp, index) => `<a href="${escapeHtml(comp)}" target="_blank" title="Ver">👁️</a> <a href="${escapeHtml(comp)}" download title="Descargar">⬇️</a>${index < fila.comprobantes.length - 1 ? " " : ""}`).join("")
                    : '<span class="sin">Sin comprobante</span>';
                const acciones = (fila.ids || []).length
                    ? fila.ids.map((id) => `<a href="?eliminar_pago=${encodeURIComponent(id)}" onclick="return confirm('¿Eliminar este pago?')" title="Eliminar pago">🗑️</a>`).join(" ")
                    : '<span class="sin">Sin acciones</span>';

                return `<tr>
<td>${escapeHtml(fila.empresa ? fila.empresa.razon : "Empresa eliminada")}</td>
<td>${escapeHtml(fila.empresa ? fila.empresa.cuit : "")}</td>
<td><span class="badge">${escapeHtml(fila.tipo || "")}</span></td>
<td>${escapeHtml(fila.plan)}</td>
<td>${dineroCliente(fila.esperado)}</td>
<td>${dineroCliente(fila.pagado)}</td>
<td><span class="estado ${estadoClase}">${escapeHtml(fila.estado)}</span></td>
<td>${escapeHtml((fila.fechas || []).join(", "))}</td>
<td>${comprobantes}</td>
<td>${acciones}</td>
</tr>`;
            }).join("");
        }

        if (deudores.length === 0) {
            noPagaronBody.innerHTML = '<tr><td colspan="9" class="sin">No hay empresas pendientes para este período y tipo.</td></tr>';
        } else {
            noPagaronBody.innerHTML = deudores.map(({ empresa, tipo, plan, esperado }) => {
                const ultimoPago = ultimoPagoEmpresaTipo(empresa.id || "", tipo);
                const ultimo = ultimoPago
                    ? `${escapeHtml(periodoNormalizado(ultimoPago.periodo))} - ${escapeHtml(ultimoPago.fecha || "")} - ${dineroCliente(ultimoPago.monto)}`
                    : '<span class="sin">Sin pagos previos</span>';
                return `<tr>
<td>${escapeHtml(empresa.razon || "")}</td>
<td>${escapeHtml(empresa.cuit || "")}</td>
<td><span class="badge">${escapeHtml(tipo)}</span></td>
<td>${escapeHtml(plan)}</td>
<td>${dineroCliente(esperado)}</td>
<td>${escapeHtml(periodoAcuerdoEmpresa(empresa, tipo) || periodo)}</td>
<td>${ultimo}</td>
<td><span class="estado estado-deudor">DEUDOR</span></td>
<td><button type="button" class="btn-small cargar-pago-informe" data-empresa="${escapeHtml(empresa.id || "")}" data-tipo="${escapeHtml(tipo)}" data-periodo="${escapeHtml(periodo)}">Cargar pago</button></td>
</tr>`;
            }).join("");

            noPagaronBody.querySelectorAll(".cargar-pago-informe").forEach((boton) => {
                boton.addEventListener("click", () => {
                    completarFormularioPago(boton.dataset.empresa, boton.dataset.tipo, boton.dataset.periodo);
                });
            });
        }
    };

    consultar.addEventListener("click", render);
    periodoInput.addEventListener("input", () => {
        if (periodoValidoCliente(periodoInput.value)) render();
    });
    tipoInput.addEventListener("change", render);
}

function configurarBuscadoresEmpresa() {
    const homeInput = document.getElementById("homeEmpresaSearch");
    const homeResultados = document.getElementById("homeEmpresaResultados");
    const fichaInput = document.getElementById("buscadorFichaEmpresa");
    const fichaResultados = document.getElementById("resultadosFichaEmpresa");

    if (homeInput && homeResultados) {
        homeInput.addEventListener("input", () => renderResultadosEmpresa(homeInput, homeResultados, 6));
    }
    if (fichaInput && fichaResultados) {
        fichaInput.addEventListener("input", () => renderResultadosEmpresa(fichaInput, fichaResultados, 20));
    }

    const acuerdoEmpresa = document.querySelector('input[name="acuerdo_empresa_id"]');
    const acuerdoTipo = document.getElementById("acuerdoTipo");
    if (acuerdoEmpresa) acuerdoEmpresa.addEventListener("change", cargarAcuerdoExistente);
    if (acuerdoTipo) acuerdoTipo.addEventListener("change", cargarAcuerdoExistente);
}

configurarTabs();
configurarCardsPlegables();
configurarEmpresaPickers();
configurarBuscadoresEmpresa();
configurarInformePeriodo();
configurarFiltrosEmpresas();
configurarFiltrosPagos();
</script>
</body>
</html>
