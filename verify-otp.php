<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/helpers.php';
require_once 'includes/OTP.php';
require_once 'includes/Mailer.php';

$error = '';
$success = '';

$emailFromSession = $_SESSION['pending_verify_email'] ?? '';
$email = $emailFromSession ?: sanitize($_GET['email'] ?? '');

// Handle resend before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['resend'])) {
	$resendEmail = sanitize($_POST['email'] ?? '');
	if ($resendEmail) {
		try {
			// Enforce 5-minute resend limit
			global $pdo;
			$check = $pdo->prepare("SELECT created_at FROM email_otps WHERE email = ? AND purpose = 'register' ORDER BY id DESC LIMIT 1");
			$check->execute([$resendEmail]);
			$row = $check->fetch();
			if ($row && isset($row['created_at'])) {
				$last = new DateTime($row['created_at']);
				$now = new DateTime();
				$diff = $now->getTimestamp() - $last->getTimestamp();
				if ($diff < 300) { // 5 minutes
					http_response_code(429);
					header('Content-Type: text/plain');
					echo 'Please wait ' . (300 - $diff) . ' seconds before requesting another code.';
					exit;
				}
			}

			$otp = otp_create($resendEmail, 'register', 10);
			$mailer = new Mailer();
			$sent = $mailer->send($resendEmail, '', 'Your new verification code', '<p>Your new verification code:</p><div style="font-size:24px;font-weight:bold;letter-spacing:4px;color:#ff6b35;">' . htmlspecialchars($otp['code']) . '</div><p>Expires at ' . htmlspecialchars($otp['expires_at']) . ' (UTC).</p>');
			// Log the resend outcome as well
			$logDir = __DIR__ . '/logs';
			if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
			@file_put_contents($logDir . '/logs_otp.txt', date('c') . " | resend | $resendEmail | SEND " . ($sent ? 'OK' : ('FAILED: ' . $mailer->getLastError())) . "\n", FILE_APPEND);

			if ($sent) {
				http_response_code(204);
			} else {
				http_response_code(500);
				header('Content-Type: text/plain');
				echo 'Email could not be sent. Please try again later.';
			}
			exit;
		} catch (Exception $e) {
			http_response_code(500);
			header('Content-Type: text/plain');
			echo 'Could not resend code. Please try again later.';
			exit;
		}
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$email = sanitize($_POST['email'] ?? '');
		$code = sanitize($_POST['code'] ?? '');
		if (!$email || !$code) {
				$error = 'Please provide your email and the verification code.';
		} else {
				$res = otp_verify($email, $code, 'register');
				if ($res['success']) {
						// Mark user as verified
						try {
								global $pdo;
								$upd = $pdo->prepare("UPDATE users SET is_verified = 1, status = 'active', updated_at = NOW() WHERE email = ?");
								$upd->execute([$email]);
								unset($_SESSION['pending_verify_email']);
								$_SESSION['flash_success'] = 'Email verified successfully. You can now sign in.';
								header('Location: login.php', true, 302);
								exit;
						} catch (Exception $e) {
								$error = 'Could not update your account verification status.';
						}
				} else {
						$error = $res['message'] ?? 'Invalid code.';
				}
		}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Verify Email - School LMS</title>
		<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
		<style>
				body { font-family: 'Poppins', sans-serif; background: #f3f4f6; margin: 0; }
				.container { max-width: 520px; margin: 60px auto; background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.06); }
				h1 { margin: 0 0 8px; font-size: 24px; }
				p { margin: 0 0 20px; color: #6b7280; font-size: 14px; }
				label { display: block; font-weight: 600; margin-bottom: 6px; }
				input { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'Poppins', sans-serif; }
				.row { display: grid; grid-template-columns: 1fr; gap: 14px; }
				.btn { background: #ff6b35; color: #fff; border: 0; padding: 12px 16px; border-radius: 8px; width: 100%; cursor: pointer; font-weight: 600; }
				.muted { color: #6b7280; font-size: 12px; margin-top: 12px; }
				.alert { padding: 12px; border-radius: 8px; margin-bottom: 14px; font-size: 14px; }
				.alert-danger { background: rgba(239,68,68,.1); color: #b91c1c; border-left: 4px solid #ef4444; }
				.alert-success { background: rgba(34,197,94,.1); color: #15803d; border-left: 4px solid #22c55e; }
			.alert-info { background: rgba(59,130,246,.1); color: #1e40af; border-left: 4px solid #3b82f6; }
			.actions { display: flex; justify-content: space-between; gap: 10px; margin-top: 10px; }
			.link { font-size: 13px; color: #3b82f6; text-decoration: none; }
			#resend-status { margin-top: 14px; display: none; }
		</style>
		<script>
			async function resendCode(e){
				e.preventDefault();
				const email = document.getElementById('email').value.trim();
				if(!email){ alert('Enter your email first.'); return; }
				const statusDiv = document.getElementById('resend-status');
				statusDiv.style.display = 'block';
				statusDiv.className = 'alert alert-info';
				statusDiv.textContent = 'Sending new code...';
				try{
					const resp = await fetch('verify-otp.php?resend=1', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'email='+encodeURIComponent(email)});
					const text = await resp.text();
					if(resp.status === 204){
						statusDiv.className = 'alert alert-success';
						statusDiv.textContent = '✓ A new verification code has been sent to your email!';
					} else if(resp.status === 429){
						statusDiv.className = 'alert alert-danger';
						statusDiv.textContent = text || 'Please wait before requesting another code.';
					} else {
						statusDiv.className = 'alert alert-danger';
						statusDiv.textContent = text || 'Failed to send code. Please try again.';
					}
				}catch(err){ 
					statusDiv.className = 'alert alert-danger';
					statusDiv.textContent = 'Network error. Please check your connection.';
				}
			}
		</script>
</head>
<body>
	<div class="container">
		<h1>Verify your email</h1>
		<p>We sent a 6-digit code to your email. Enter it below to activate your account.</p>

		<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
		<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
		
		<div id="resend-status"></div>

		<form method="POST">
			<div class="row">
				<div>
					<label for="email">Email</label>
					<input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required />
				</div>
				<div>
					<label for="code">Verification Code</label>
					<input type="text" id="code" name="code" placeholder="6-digit code" maxlength="6" required />
				</div>
				<button type="submit" class="btn">Verify</button>
			</div>
		</form>
		<div class="actions">
			<a href="#" class="link" onclick="resendCode(event)">Resend code</a>
			<a href="login.php" class="link">Back to sign in</a>
		</div>
		<div class="muted">Code expires in 10 minutes. Check spam folder if you don't see it.</div>
	</div>

 
</body>
</html>
