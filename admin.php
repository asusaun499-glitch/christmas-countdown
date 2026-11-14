<?php
declare(strict_types=1);
require __DIR__ . '/settings.php';
date_default_timezone_set('Asia/Bangkok');

$password = getenv('COUNTDOWN_ADMIN_PASSWORD') ?: '';
$error = '';
$notice = '';
$cookieName = 'maria_countdown_admin';
$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (bool)getenv('VERCEL');
$loggedIn = false;
$csrfToken = '';
$cookie = explode('.', (string)($_COOKIE[$cookieName] ?? ''), 2);
if ($password !== '' && count($cookie) === 2 && hash_equals(hash_hmac('sha256', $cookie[0], $password), $cookie[1])) {
    $encoded = strtr($cookie[0], '-_', '+/');
    $claims = json_decode((string)base64_decode($encoded . str_repeat('=', (4 - strlen($encoded) % 4) % 4)), true);
    if (is_array($claims) && (int)($claims['exp'] ?? 0) > time() && is_string($claims['csrf'] ?? null)) {
        $loggedIn = true;
        $csrfToken = $claims['csrf'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        if ($password !== '' && hash_equals($password, (string)($_POST['password'] ?? ''))) {
            $csrfToken = bin2hex(random_bytes(32));
            $claims = rtrim(strtr(base64_encode(json_encode(['exp' => time() + 28800, 'csrf' => $csrfToken], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
            $signature = hash_hmac('sha256', $claims, $password);
            setcookie($cookieName, $claims . '.' . $signature, [
                'expires' => time() + 28800,
                'path' => '/',
                'httponly' => true,
                'secure' => $secureCookie,
                'samesite' => 'Lax',
            ]);
            header('Location: admin.php');
            exit;
        }
        $error = $password === '' ? 'ยังไม่ได้ตั้งค่า COUNTDOWN_ADMIN_PASSWORD ในเซิร์ฟเวอร์' : 'รหัสผ่านไม่ถูกต้อง';
    } elseif (!$loggedIn) {
        $error = 'กรุณาเข้าสู่ระบบใหม่';
    } elseif (!hash_equals($csrfToken, (string)($_POST['csrf'] ?? ''))) {
        $error = 'คำขอหมดอายุ กรุณาลองใหม่';
    } elseif (isset($_POST['logout'])) {
        setcookie($cookieName, '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'secure' => $secureCookie, 'samesite' => 'Lax']);
        header('Location: admin.php');
        exit;
    } else {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'reset') {
            $saved = save_countdown_target(CHRISTMAS_TARGET);
            $notice = $saved ? 'รีเซ็ตเป็น 25 ธันวาคม 2569 เวลา 00:00 น. แล้ว' : '';
            if (!$saved) $error = 'บันทึกไม่ได้: ตรวจสอบสิทธิ์เขียนไฟล์ หรือกำหนด Supabase persistent storage บน Vercel';
        } elseif ($action === 'test_now' || $action === 'test_10') {
            $seconds = $action === 'test_10' ? 10 : 0;
            $testTarget = (new DateTimeImmutable('now', new DateTimeZone('Asia/Bangkok')))->modify('+' . $seconds . ' seconds')->format(DateTimeInterface::ATOM);
            $saved = save_countdown_target($testTarget);
            $notice = $saved ? ($seconds ? 'ตั้งทดสอบ: จะฉลองใน 10 วินาที' : 'ตั้งทดสอบ: เริ่มฉลองทันที') : '';
            if (!$saved) $error = 'บันทึกไม่ได้: ตรวจสอบสิทธิ์เขียนไฟล์ หรือกำหนด Supabase persistent storage บน Vercel';
        } elseif ($action === 'set_time') {
            $input = trim((string)($_POST['target'] ?? ''));
            $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $input, new DateTimeZone('Asia/Bangkok'));
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] || $dateErrors['error_count']))) {
                $error = 'กรุณาเลือกวันและเวลาให้ถูกต้อง';
            } else {
                $saved = save_countdown_target($date->format(DateTimeInterface::ATOM));
                $notice = $saved ? 'บันทึกวันเวลาใหม่แล้ว' : '';
                if (!$saved) $error = 'บันทึกไม่ได้: ตรวจสอบสิทธิ์เขียนไฟล์ หรือกำหนด Supabase persistent storage บน Vercel';
            }
        }
    }
}

$target = countdown_target();
$inputValue = $target->setTimezone(new DateTimeZone('Asia/Bangkok'))->format('Y-m-d\TH:i');
?><!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#080b0b"><title>จัดการ Countdown · Maria Town</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet"><style>
:root{color-scheme:dark;--bg:#080b0b;--card:#111613;--line:#ffffff20;--text:#f2eddf;--muted:#a6a69b;--green:#b6cf96;--red:#e38b78}*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(ellipse at 70% 10%,#1b2a20,transparent 44%),var(--bg);color:var(--text);font-family:Kanit,sans-serif}.wrap{width:min(620px,calc(100% - 32px));margin:8vh auto}.brand{font:11px 'DM Mono',monospace;letter-spacing:.14em;color:var(--green);margin-bottom:28px}.card{border:1px solid var(--line);background:#111613e8;padding:clamp(24px,5vw,42px);box-shadow:0 24px 90px #0007}h1{font-size:30px;font-weight:400;margin:0 0 7px}.sub{color:var(--muted);margin:0 0 28px;font-size:14px}.current{border:1px solid var(--line);padding:15px 17px;margin-bottom:23px}.current small{display:block;font:10px 'DM Mono',monospace;color:var(--muted);letter-spacing:.1em;margin-bottom:7px}.current strong{font-size:17px;font-weight:400;color:var(--green)}label{display:block;color:var(--muted);font-size:13px;margin:0 0 9px}input{width:100%;padding:13px 14px;background:#080b0b;color:var(--text);border:1px solid var(--line);font:15px 'DM Mono',monospace;margin-bottom:12px}button,.link{border:1px solid var(--line);background:transparent;color:var(--text);padding:12px 15px;font:13px Kanit,sans-serif;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;min-height:44px}button:hover,.link:hover{border-color:var(--green);color:var(--green)}button.primary{background:var(--green);border-color:var(--green);color:#10140e}button.primary:hover{background:#d2e4bb;color:#10140e}.actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:17px}.divider{border:0;border-top:1px solid var(--line);margin:27px 0}.message{padding:11px 14px;margin:0 0 17px;border:1px solid #b6cf9666;color:var(--green)}.error{border-color:#e38b7866;color:var(--red)}.login{display:grid;gap:8px}.help{font-size:12px;line-height:1.7;color:var(--muted);margin:18px 0 0}.topline{display:flex;align-items:center;justify-content:space-between;gap:12px}.topline h1{margin:0}.logout{margin:0}.danger{color:var(--red)}@media(max-width:480px){.wrap{margin:5vh auto}.card{padding:24px 19px}.actions>*{flex:1}.topline{align-items:flex-start}}
</style></head><body><main class="wrap"><div class="brand">✳ &nbsp; MARIA TOWN · COUNTDOWN ADMIN</div><section class="card">
<?php if ($error !== ''): ?><p class="message error"><?=htmlspecialchars($error, ENT_QUOTES, 'UTF-8')?></p><?php endif ?>
<?php if ($notice !== ''): ?><p class="message"><?=htmlspecialchars($notice, ENT_QUOTES, 'UTF-8')?></p><?php endif ?>
<?php if (!$loggedIn): ?>
<h1>เข้าสู่ระบบแอดมิน</h1><p class="sub">จัดการวันเปิดแมพและทดสอบการฉลอง</p><form method="post" class="login"><label for="password">รหัสผ่านแอดมิน</label><input id="password" name="password" type="password" autocomplete="current-password" required><button class="primary" name="login" value="1">เข้าสู่ระบบ</button></form><p class="help">ตั้งรหัสผ่านด้วย environment variable <code>COUNTDOWN_ADMIN_PASSWORD</code> ก่อนเปิดหน้าแอดมิน</p>
<?php else: ?>
<div class="topline"><h1>จัดการเวลาเปิดแมพ</h1><form method="post" class="logout"><input type="hidden" name="csrf" value="<?=htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8')?>"><button name="logout" value="1">ออกจากระบบ</button></form></div><p class="sub">ปรับเวลา ทดสอบฉลอง หรือคืนค่าเทศกาล</p><div class="current"><small>เวลาที่ตั้งอยู่ตอนนี้ · BANGKOK TIME</small><strong><?=htmlspecialchars($target->setTimezone(new DateTimeZone('Asia/Bangkok'))->format('d/m/Y · H:i:s'), ENT_QUOTES, 'UTF-8')?></strong></div>
<form method="post"><input type="hidden" name="csrf" value="<?=htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8')?>"><input type="hidden" name="action" value="set_time"><label for="target">ตั้งวันและเวลาใหม่ (เวลาไทย)</label><input id="target" type="datetime-local" name="target" value="<?=htmlspecialchars($inputValue, ENT_QUOTES, 'UTF-8')?>" required><button class="primary" type="submit">บันทึกเวลา</button></form>
<hr class="divider"><label>ทดสอบระบบฉลอง</label><div class="actions"><form method="post"><input type="hidden" name="csrf" value="<?=htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8')?>"><input type="hidden" name="action" value="test_10"><button type="submit">ทดสอบใน 10 วินาที</button></form><form method="post"><input type="hidden" name="csrf" value="<?=htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8')?>"><input type="hidden" name="action" value="test_now"><button type="submit">ทดสอบทันที</button></form></div>
<hr class="divider"><label>คืนเวลานับถอยหลังเทศกาล</label><form method="post" onsubmit="return confirm('คืนวันเวลาเป็น 25 ธันวาคม 2569 เวลา 00:00 น.?')"><input type="hidden" name="csrf" value="<?=htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8')?>"><input type="hidden" name="action" value="reset"><button class="danger" type="submit">รีเซ็ตเป็นวันคริสต์มาส 25/12/2569</button></form>
<p class="help">หลังบันทึก หน้าเว็บจะรับเวลาใหม่อัตโนมัติภายในไม่กี่วินาที ใช้ JSON file storage บน PHP hosting หรือ Supabase storage บน Vercel</p>
<?php endif ?></section></main></body></html>
