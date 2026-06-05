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
        return substr($periodo, 5, 2) . "/" . substr($periodo, 2, 2);
    }
    return $periodo;
}

function periodoValido($periodo) {
    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', trim($periodo ?? ""))) {
        return false;
    }
    return true;
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

    $editado = false;
    foreach ($empresas as $k => $emp) {
        if (($emp["id"] ?? "") === $id) {
            $nueva["fecha_carga"] = $emp["fecha_carga"] ?? date("Y-m-d H:i:s");
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

if (isset($_POST["guardar_pago"])) {
    $id = $_POST["pago_id"] ?: uniqid("pago_");
    $comprobante = $_POST["comprobante_actual"] ?? "";
    $periodo = trim($_POST["periodo"] ?? "");

    if (!periodoValido($periodo)) {
        $errorPago = "El periodo debe tener formato MM/AA.";
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
    $empresas = array_values(array_filter($empresas, fn($e) => ($e["id"] ?? "") !== $id));
    $pagos = array_values(array_filter($pagos, fn($p) => ($p["empresa_id"] ?? "") !== $id));
    guardarJson($empresasFile, $empresas);
    guardarJson($pagosFile, $pagos);
    header("Location: index.php");
    exit;
}

if (isset($_GET["eliminar_pago"])) {
    $id = $_GET["eliminar_pago"];
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

$totalDeuda = 0;
$totalCobrado = 0;

foreach ($empresas as $emp) {
    $totalDeuda += floatval($emp["deuda_os"] ?? 0);
    $totalDeuda += floatval($emp["deuda_sindicato"] ?? 0);
    $totalDeuda += floatval($emp["deuda_mutual"] ?? 0);
}

foreach ($pagos as $p) {
    $totalCobrado += floatval($p["monto"] ?? 0);
}

$saldoPendiente = $totalDeuda - $totalCobrado;
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
.card{background:white;border-radius:16px;box-shadow:0 5px 18px #0001;padding:20px;margin-bottom:20px}
.resumen{display:grid;grid-template-columns:repeat(3,1fr);gap:15px}
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
.filters-grid.empresas{grid-template-columns:2fr 1fr auto}
.filters input,.filters select{width:100%;background:white}
.filters button{white-space:nowrap}
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
@media(max-width:1000px){.grid,.resumen,.filters-grid,.filters-grid.empresas{grid-template-columns:1fr}table{display:block;overflow-x:auto}}
</style>
</head>
<body>

<header>
<h1>Registro de Pagos - Empresas Deudoras</h1>
<a href="?logout=1">Salir</a>
</header>

<main>

<div class="card resumen">
<div class="box"><div class="label">Deuda total cargada</div><div class="num"><?= dinero($totalDeuda) ?></div></div>
<div class="box"><div class="label">Total cobrado</div><div class="num"><?= dinero($totalCobrado) ?></div></div>
<div class="box"><div class="label">Saldo pendiente</div><div class="num"><?= dinero($saldoPendiente) ?></div></div>
</div>

<div class="card">
<h2><?= $editarEmpresa ? "Editar empresa" : "Cargar empresa" ?></h2>

<form method="post">
<input type="hidden" name="empresa_id" value="<?= e($editarEmpresa["id"] ?? "") ?>">

<div class="grid">
<input type="text" name="razon" placeholder="Razón social" required value="<?= e($editarEmpresa["razon"] ?? "") ?>">
<input type="text" name="cuit" placeholder="CUIT" required value="<?= e($editarEmpresa["cuit"] ?? "") ?>">
<input type="number" step="0.01" min="0" name="deuda_os" placeholder="Deuda Obra Social" value="<?= e($editarEmpresa["deuda_os"] ?? "") ?>">
<input type="number" step="0.01" min="0" name="deuda_sindicato" placeholder="Deuda Sindicato" value="<?= e($editarEmpresa["deuda_sindicato"] ?? "") ?>">
<input type="number" step="0.01" min="0" name="deuda_mutual" placeholder="Deuda Mutual" value="<?= e($editarEmpresa["deuda_mutual"] ?? "") ?>">
</div>

<textarea name="observaciones_empresa" placeholder="Observaciones empresa"><?= e($editarEmpresa["observaciones"] ?? "") ?></textarea>

<br><br>
<button name="guardar_empresa"><?= $editarEmpresa ? "Guardar cambios empresa" : "Guardar empresa" ?></button>
<?php if($editarEmpresa): ?>
<a class="btn-cancelar" href="index.php">Cancelar</a>
<?php endif; ?>
</form>
</div>

<div class="card">
<h2><?= $editarPago ? "Editar pago" : "Cargar pago" ?></h2>

<form method="post" enctype="multipart/form-data">
<input type="hidden" name="pago_id" value="<?= e($editarPago["id"] ?? "") ?>">
<input type="hidden" name="comprobante_actual" value="<?= e($editarPago["comprobante"] ?? "") ?>">

<div class="grid">
<select name="empresa_id" required>
<option value="">Seleccionar empresa</option>
<?php foreach($empresas as $emp): ?>
<option value="<?= e($emp["id"]) ?>" <?= (($editarPago["empresa_id"] ?? "") === $emp["id"]) ? "selected" : "" ?>>
<?= e($emp["razon"]) ?> - <?= e($emp["cuit"]) ?>
</option>
<?php endforeach; ?>
</select>

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
<input type="text" name="periodo" class="periodo-input" placeholder="MM/AA" maxlength="5" inputmode="numeric" pattern="(0[1-9]|1[0-2])\/[0-9]{2}" required value="<?= e(periodoParaInput($editarPago["periodo"] ?? "")) ?>">
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

<div class="card">
<h2>Empresas registradas</h2>

<div class="filters">
<div class="filters-grid empresas">
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
<th>Deuda OS</th>
<th>Saldo OS</th>
<th>Deuda Sindicato</th>
<th>Saldo Sindicato</th>
<th>Deuda Mutual</th>
<th>Saldo Mutual</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php if(empty($empresas)): ?>
<tr><td colspan="9" class="sin">Todavía no hay empresas cargadas.</td></tr>
<?php endif; ?>

<?php foreach($empresas as $emp):
$pagadoOS = totalPagado($pagos, $emp["id"], "Obra Social");
$pagadoSind = totalPagado($pagos, $emp["id"], "Sindicato");
$pagadoMutual = totalPagado($pagos, $emp["id"], "Mutual");

$saldoOS = floatval($emp["deuda_os"] ?? 0) - $pagadoOS;
$saldoSind = floatval($emp["deuda_sindicato"] ?? 0) - $pagadoSind;
$saldoMutual = floatval($emp["deuda_mutual"] ?? 0) - $pagadoMutual;
$estadoEmpresa = ($saldoOS > 0 || $saldoSind > 0 || $saldoMutual > 0) ? "deuda" : "cancelada";
?>
<tr class="fila-empresa" data-busqueda="<?= e(($emp["razon"] ?? "") . " " . ($emp["cuit"] ?? "")) ?>" data-estado="<?= e($estadoEmpresa) ?>">
<td><?= e($emp["razon"]) ?></td>
<td><?= e($emp["cuit"]) ?></td>
<td><?= dinero($emp["deuda_os"] ?? 0) ?></td>
<td class="<?= $saldoOS <= 0 ? 'saldo-ok' : 'saldo-debe' ?>"><?= dinero($saldoOS) ?></td>
<td><?= dinero($emp["deuda_sindicato"] ?? 0) ?></td>
<td class="<?= $saldoSind <= 0 ? 'saldo-ok' : 'saldo-debe' ?>"><?= dinero($saldoSind) ?></td>
<td><?= dinero($emp["deuda_mutual"] ?? 0) ?></td>
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

<div class="card">
<h2>Pagos registrados</h2>

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
<input type="text" id="filtroPagoPeriodo" class="periodo-input" placeholder="MM/AA" maxlength="5" inputmode="numeric">
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

</main>
<script>
function formatearPeriodo(valor) {
    const numeros = (valor || "").replace(/\D/g, "").slice(0, 4);
    if (numeros.length <= 2) return numeros;
    return numeros.slice(0, 2) + "/" + numeros.slice(2);
}

function periodoValidoCliente(valor) {
    return /^(0[1-9]|1[0-2])\/\d{2}$/.test(valor || "");
}

document.querySelectorAll(".periodo-input").forEach((input) => {
    input.addEventListener("input", () => {
        input.value = formatearPeriodo(input.value);
    });
});

const pagoForm = document.querySelector('form[enctype="multipart/form-data"]');
if (pagoForm) {
    pagoForm.addEventListener("submit", (event) => {
        const periodo = pagoForm.querySelector('input[name="periodo"]');
        if (periodo && !periodoValidoCliente(periodo.value)) {
            event.preventDefault();
            alert("El periodo debe tener formato MM/AA.");
            periodo.focus();
        }
    });
}

function textoNormalizado(valor) {
    return (valor || "").toString().toLowerCase();
}

function configurarFiltrosEmpresas() {
    const texto = document.getElementById("filtroEmpresaTexto");
    const estado = document.getElementById("filtroEmpresaEstado");
    const limpiar = document.getElementById("limpiarFiltrosEmpresas");
    const filas = Array.from(document.querySelectorAll(".fila-empresa"));
    if (!texto || !estado || !limpiar) return;

    const aplicar = () => {
        const busqueda = textoNormalizado(texto.value);
        const estadoValor = estado.value;

        filas.forEach((fila) => {
            const coincideTexto = textoNormalizado(fila.dataset.busqueda).includes(busqueda);
            const coincideEstado = !estadoValor || fila.dataset.estado === estadoValor;
            fila.classList.toggle("fila-oculta", !(coincideTexto && coincideEstado));
        });
    };

    texto.addEventListener("input", aplicar);
    estado.addEventListener("change", aplicar);
    limpiar.addEventListener("click", () => {
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

configurarFiltrosEmpresas();
configurarFiltrosPagos();
</script>
</body>
</html>
