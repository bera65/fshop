<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/install_gate.php';

if (Fshop_is_installed()) {
	header('Location: ../');
	exit;
}

require_once dirname(__DIR__) . '/core/Installer.php';

$step = max(1, min(4, (int) ($_GET['step'] ?? 1)));
$error = '';
$success = '';
$requirements = Installer::requirements();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = (string) ($_POST['action'] ?? '');

	if ($action === 'install') {
		$result = Installer::install($_POST);

		if ($result['success']) {
			$success = $result['message'];
			$step = 4;
		} else {
			$error = $result['message'];
			$step = 3;
		}
	}
}

$defaults = [
	'db_host' => 'localhost',
	'db_name' => 'frisay',
	'db_user' => 'root',
	'db_pass' => '',
	'site_name' => 'FriSay',
	'site_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/install')), '/'),
	'rewrite_base' => rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/install')), '/') . '/',
	'admin_name' => 'Admin',
	'admin_email' => 'admin@example.com',
	'theme' => 'blue',
	'shop_lang' => 'tr',
	'admin_lang' => 'tr',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>FriSay Installation</title>
	<link rel="stylesheet" href="../templates/admin/css/bootstrap.min.css">
	<link rel="stylesheet" href="assets/install.css">
</head>
<body>
<div class="install-wrap">
	<div class="install-card">
		<h1 class="install-title">FriSay Installation</h1>
		<p class="install-subtitle">Step <?php echo (int) $step; ?> / 4</p>

		<div class="install-steps">
			<div class="install-step<?php echo $step === 1 ? ' is-active' : ''; ?>">Requirements</div>
			<div class="install-step<?php echo $step === 2 ? ' is-active' : ''; ?>">Database</div>
			<div class="install-step<?php echo $step === 3 ? ' is-active' : ''; ?>">Website &amp; Admin</div>
			<div class="install-step<?php echo $step === 4 ? ' is-active' : ''; ?>">Completed</div>
		</div>

		<?php if ($error !== '') { ?>
		<div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
		<?php } ?>

		<?php if ($step === 1) { ?>
		<ul class="check-list">
			<?php foreach ($requirements['items'] as $item) { ?>
			<li class="<?php echo !empty($item['ok']) ? 'check-ok' : 'check-fail'; ?>">
				<?php echo htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8'); ?>
				<small><?php echo htmlspecialchars((string) ($item['hint'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
			</li>
			<?php } ?>
		</ul>
		<p style="margin-top:24px;">
			<?php if ($requirements['ok']) { ?>
			<a class="btn btn-primary" href="?step=2">Continue</a>
			<?php } else { ?>
			<span class="check-fail">You cannot continue until all requirements are met.</span>
			<?php } ?>
		</p>
		<?php } ?>

		<?php if ($step === 2) { ?>
		<form method="get">
			<input type="hidden" name="step" value="3">
			<div style="margin-bottom:12px;">
				<label class="form-label">Database Host</label>
				<input type="text" name="db_host" class="form-control" value="<?php echo htmlspecialchars($defaults['db_host'], ENT_QUOTES, 'UTF-8'); ?>" required>
			</div>
			<div style="margin-bottom:12px;">
				<label class="form-label">Database Name</label>
				<input type="text" name="db_name" class="form-control" value="<?php echo htmlspecialchars((string) ($_GET['db_name'] ?? $defaults['db_name']), ENT_QUOTES, 'UTF-8'); ?>" required>
			</div>
			<div style="margin-bottom:12px;">
				<label class="form-label">Username</label>
				<input type="text" name="db_user" class="form-control" value="<?php echo htmlspecialchars((string) ($_GET['db_user'] ?? $defaults['db_user']), ENT_QUOTES, 'UTF-8'); ?>" required>
			</div>
			<div style="margin-bottom:12px;">
				<label class="form-label">Password</label>
				<input type="password" name="db_pass" class="form-control" value="">
			</div>
			<button type="submit" class="btn btn-primary">Continue</button>
			<a href="?step=1" class="btn btn-secondary">Back</a>
		</form>
		<?php } ?>

		<?php if ($step === 3) { ?>
		<form method="post">
			<input type="hidden" name="action" value="install">
			<input type="hidden" name="db_host" value="<?php echo htmlspecialchars((string) ($_GET['db_host'] ?? $defaults['db_host']), ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" name="db_name" value="<?php echo htmlspecialchars((string) ($_GET['db_name'] ?? $defaults['db_name']), ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" name="db_user" value="<?php echo htmlspecialchars((string) ($_GET['db_user'] ?? $defaults['db_user']), ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" name="db_pass" value="<?php echo htmlspecialchars((string) ($_GET['db_pass'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

			<div style="margin-bottom:12px;">
				<label class="form-label">Website Name</label>
				<input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($defaults['site_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
			</div>
			<div style="margin-bottom:12px;">
				<label class="form-label">Website URL</label>
				<input type="url" name="site_url" class="form-control" value="<?php echo htmlspecialchars($defaults['site_url'], ENT_QUOTES, 'UTF-8'); ?>" required>
			</div>
			<div style="margin-bottom:12px;">
				<label class="form-label">Rewrite Base</label>
				<input type="text" name="rewrite_base" class="form-control" value="<?php echo htmlspecialchars($defaults['rewrite_base'], ENT_QUOTES, 'UTF-8'); ?>" required>
			</div>
			<div style="margin-bottom:12px;">
				<label class="form-label">Theme</label>
				<select name="theme" class="form-control">
					<option value="blue">blue</option>
					<option value="nova">nova</option>
					<option value="fyazilim">fyazilim</option>
					<option value="default">default</option>
				</select>
			</div>
			<div style="margin-bottom:12px;">
				<label class="form-label">Administrator Name</label>
				<input type="text" name="admin_name" class="form-control" value="<?php echo htmlspecialchars($defaults['admin_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
			</div>
			<div style="margin-bottom:12px;">
				<label class="form-label">Administrator Email</label>
				<input type="email" name="admin_email" class="form-control" value="<?php echo htmlspecialchars($defaults['admin_email'], ENT_QUOTES, 'UTF-8'); ?>" required>
			</div>
			<div style="margin-bottom:12px;">
				<label class="form-label">Administrator Password (minimum 8 characters)</label>
				<input type="password" name="admin_password" class="form-control" required minlength="8">
			</div>
			<div style="margin-bottom:16px;">
				<label><input type="checkbox" name="install_demo" value="1"> Install demo data</label>
			</div>
			<button type="submit" class="btn btn-primary">Start Installation</button>
			<a href="?step=2" class="btn btn-secondary">Back</a>
		</form>
		<?php } ?>

		<?php if ($step === 4) { ?>
		<div class="alert alert-success"><?php echo htmlspecialchars($success !== '' ? $success : 'Installation completed successfully.', ENT_QUOTES, 'UTF-8'); ?></div>
		<p>You can now log in to the admin panel and install modules.</p>
		<p><a class="btn btn-primary" href="../admin/">Go to Admin Panel</a></p>
		<p><small>For security reasons, disable access to the <code>/install/</code> directory in production.</small></p>
		<?php } ?>
	</div>
</div>
</body>
</html>