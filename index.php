<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function redirectHome(): never {
    header('Location: index.php');
    exit;
}

$error = '';
$edit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
    }
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM envios WHERE id = ?');
            $stmt->execute([$id]);
        }
        redirectHome();
    }
    if ($action === 'save') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
        $destinatario = trim((string)($_POST['destinatario'] ?? ''));
        $direccion = trim((string)($_POST['direccion'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));

        if ($destinatario === '' || $direccion === '' || $descripcion === '') {
            $error = 'Completa todos los campos antes de guardar.';
            $edit = ['id' => $id, 'destinatario' => $destinatario, 'direccion' => $direccion, 'descripcion' => $descripcion];
        } elseif (mb_strlen($destinatario) > 150 || mb_strlen($direccion) > 255) {
            $error = 'El destinatario admite hasta 150 caracteres y la dirección hasta 255.';
            $edit = ['id' => $id, 'destinatario' => $destinatario, 'direccion' => $direccion, 'descripcion' => $descripcion];
        } else {
            if ($id) {
                $stmt = $pdo->prepare('UPDATE envios SET destinatario=?, direccion=?, descripcion=? WHERE id=?');
                $stmt->execute([$destinatario, $direccion, $descripcion, $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO envios (destinatario, direccion, descripcion) VALUES (?, ?, ?)');
                $stmt->execute([$destinatario, $direccion, $descripcion]);
            }
            redirectHome();
        }
    }
}

if (isset($_GET['editar']) && !$edit) {
    $id = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare('SELECT id, destinatario, direccion, descripcion FROM envios WHERE id=?');
        $stmt->execute([$id]);
        $edit = $stmt->fetch() ?: null;
    }
}

$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
    $stmt = $pdo->prepare('SELECT * FROM envios WHERE destinatario LIKE ? OR direccion LIKE ? OR descripcion LIKE ? ORDER BY creado_en DESC, id DESC');
    $like = '%' . $q . '%';
    $stmt->execute([$like, $like, $like]);
    $envios = $stmt->fetchAll();
} else {
    $envios = $pdo->query('SELECT * FROM envios ORDER BY creado_en DESC, id DESC')->fetchAll();
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Gestión de envíos</title>
<style>
:root{--primary:#2457c5;--dark:#172033;--muted:#65718a;--line:#e2e7f0;--bg:#f4f7fb}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--dark);font:15px/1.5 system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
header{background:linear-gradient(120deg,#183c91,#2d6be0);color:#fff;padding:28px 20px}
.wrap{max-width:1100px;margin:0 auto;padding:0 18px}header h1{margin:0;font-size:27px}header p{margin:5px 0 0;color:#dbe7ff}
main{padding:25px 0 45px}.layout{display:grid;grid-template-columns:330px 1fr;gap:20px;align-items:start}
.panel{background:#fff;border:1px solid var(--line);border-radius:14px;padding:20px;box-shadow:0 5px 20px #1a2b4d0a}
h2{font-size:18px;margin:0 0 16px}label{display:block;font-weight:650;margin:13px 0 6px}
input[type=text],textarea{width:100%;border:1px solid #cbd4e3;border-radius:8px;padding:10px 11px;font:inherit;background:#fff}
textarea{min-height:110px;resize:vertical}.btn{display:inline-block;border:0;border-radius:8px;padding:10px 14px;background:var(--primary);color:white;font:inherit;font-weight:650;text-decoration:none;cursor:pointer}
.btn.secondary{background:#eaf0fc;color:#234a9f}.btn.danger{background:#fff0f0;color:#a52121}.actions{display:flex;gap:8px;flex-wrap:wrap}.error{background:#fff0f0;border:1px solid #ffd0d0;color:#9d2020;border-radius:8px;padding:10px;margin-bottom:14px}
.search{display:flex;gap:8px;margin-bottom:15px}.search input{flex:1;min-width:0}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:12px 10px;border-bottom:1px solid var(--line);vertical-align:top}th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted)}td small{color:var(--muted)}.desc{white-space:pre-wrap;min-width:150px;max-width:280px;overflow-wrap:anywhere}.empty{text-align:center;padding:34px 10px;color:var(--muted)}
@media(max-width:800px){.layout{grid-template-columns:1fr}.panel{padding:16px}.search{flex-wrap:wrap}.search input{flex-basis:100%}}
</style>
</head>
<body>
<header><div class="wrap"><h1>Gestión de envíos</h1><p>Registra, consulta, actualiza y elimina envíos desde un solo lugar.</p></div></header>
<main class="wrap">
<div class="layout">
<section class="panel">
<h2><?= $edit ? 'Editar envío' : 'Registrar envío' ?></h2>
<?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
<form method="post" action="index.php">
<input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
<input type="hidden" name="action" value="save">
<input type="hidden" name="id" value="<?= h(isset($edit['id']) ? (string)$edit['id'] : '') ?>">
<label for="destinatario">Destinatario</label>
<input id="destinatario" name="destinatario" type="text" maxlength="150" required value="<?= h($edit['destinatario'] ?? '') ?>" placeholder="Nombre del destinatario">
<label for="direccion">Dirección</label>
<input id="direccion" name="direccion" type="text" maxlength="255" required value="<?= h($edit['direccion'] ?? '') ?>" placeholder="Dirección de entrega">
<label for="descripcion">Descripción</label>
<textarea id="descripcion" name="descripcion" required placeholder="Contenido o detalles del envío"><?= h($edit['descripcion'] ?? '') ?></textarea>
<div class="actions" style="margin-top:16px">
<button class="btn" type="submit"><?= $edit ? 'Guardar cambios' : 'Crear envío' ?></button>
<?php if ($edit): ?><a class="btn secondary" href="index.php">Cancelar</a><?php endif; ?>
</div>
</form>
</section>
<section class="panel">
<h2>Envíos registrados <span style="color:#65718a;font-weight:500">(<?= count($envios) ?>)</span></h2>
<form class="search" method="get" action="index.php">
<input type="text" name="q" value="<?= h($q) ?>" placeholder="Buscar por destinatario, dirección o descripción">
<button class="btn" type="submit">Buscar</button>
<?php if ($q !== ''): ?><a class="btn secondary" href="index.php">Limpiar</a><?php endif; ?>
</form>
<?php if (!$envios): ?>
<div class="empty"><?= $q !== '' ? 'No hay resultados para esa búsqueda.' : 'Todavía no hay envíos. Usa el formulario para registrar el primero.' ?></div>
<?php else: ?>
<div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Destinatario / dirección</th><th>Descripción</th><th>Registro</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach ($envios as $envio): ?>
<tr>
<td>#<?= (int)$envio['id'] ?></td>
<td><strong><?= h($envio['destinatario']) ?></strong><br><?= h($envio['direccion']) ?></td>
<td class="desc"><?= h($envio['descripcion']) ?></td>
<td><small><?= h(date('d/m/Y H:i', strtotime($envio['creado_en']))) ?></small></td>
<td><div class="actions">
<a class="btn secondary" href="index.php?editar=<?= (int)$envio['id'] ?>">Editar</a>
<form method="post" action="index.php" onsubmit="return confirm('¿Seguro que deseas eliminar este envío?');">
<input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?= (int)$envio['id'] ?>">
<button class="btn danger" type="submit">Eliminar</button>
</form>
</div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section>
</div>
</main>
</body>
</html>
