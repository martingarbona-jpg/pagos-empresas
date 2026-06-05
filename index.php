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

    if (!empty($_FILES["comprobante"]["name"])) {
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

    $nuevo = [
        "id" => $id,
        "empresa_id" => $_POST["empresa_id"] ?? "",
        "fecha" => $_POST["fecha"] ?? "",
        "tipo" => $_POST["tipo"] ?? "",
        "forma_pago" => $_POST["forma_pago"] ?? "",
        "monto" => floatval($_POST["monto"] ?? 0),
        "pago_tipo" => $_POST["pago_tipo"] ?? "Pago total",
        "cuotas" => $_POST["cuotas"] ?? "",
        "periodo" => $_POST["periodo"] ?? "",
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
table{width:100%;border-collapse:collapse}
th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left;font-size:14px}
th{background:#087a46;color:white}
.acciones a{text-decoration:none;margin-right:8px;font-size:18px}
.badge{background:#eaf7f0;color:#087a46;padding:4px 8px;border-radius:20px;font-weight:bold;font-size:12px}
.sin{color:#999}
.saldo-ok{color:#087a46;font-weight:bold}
.saldo-debe{color:#b00020;font-weight:bold}
@media(max-width:1000px){.grid,.resumen{grid-template-columns:1fr}table{display:block;overflow-x:auto}}
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
<input type="month" name="periodo" required value="<?= e($editarPago["periodo"] ?? "") ?>">
<input type="file" name="comprobante" accept=".pdf,.jpg,.jpeg,.png">
</div>

<?php if($editarPago && !empty($editarPago["comprobante"])): ?>
<p>Comprobante actual: <a href="<?= e($editarPago["comprobante"]) ?>" target="_blank">Ver</a></p>
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
?>
<tr>
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
?>
<tr>
<td><?= e($p["fecha"] ?? "") ?></td>
<td><?= e($emp["razon"] ?? "Empresa eliminada") ?></td>
<td><?= e($emp["cuit"] ?? "") ?></td>
<td><span class="badge"><?= e($p["tipo"] ?? "") ?></span></td>
<td><?= e($p["forma_pago"] ?? "") ?></td>
<td><?= e($p["periodo"] ?? "") ?></td>
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
</body>
</html>