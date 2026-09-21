<?php
/* =============================================
   IDEA MARKET — DATABASE CONNECTION
   includes/db.php
   ============================================= */

/* ── Timezone: Africa/Douala (WAT, UTC+1) ──── */
date_default_timezone_set('Africa/Douala');

define('DB_HOST',    'localhost');
define('DB_NAME',    'idea_market');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');
define('SITE_NAME',   'IdeaMarket');
define('SITE_URL',    'http://localhost/idea-market');
define('UPLOAD_DIR',  __DIR__ . '/../assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('TZ_LABEL',    'WAT (West Africa Time)'); // Africa/Douala

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    // Sync MySQL session timezone with PHP timezone (WAT = UTC+1)
    $pdo->exec("SET time_zone = '+01:00'");
} catch (PDOException $e) {
    error_log("DB Connection Error: " . $e->getMessage());

    // Detect whether this is an AJAX/API call or a normal page request
    $isApiRequest = (
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false ||
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    );

    if ($isApiRequest) {
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection failed. Please try again later.']);
    } else {
        http_response_code(503);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Connection Error — IdeaMarket</title>
        <style>
          *{box-sizing:border-box;margin:0;padding:0}
          body{font-family:"DM Sans",sans-serif;background:#0f2347;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
          .box{background:#fff;border-radius:16px;padding:3rem 2.5rem;max-width:480px;width:100%;text-align:center;box-shadow:0 24px 80px rgba(0,0,0,.4)}
          .icon{font-size:3rem;margin-bottom:1rem}
          h2{color:#1a3a6b;font-size:1.4rem;margin-bottom:.75rem}
          p{color:#64748b;font-size:.9rem;line-height:1.6;margin-bottom:1.5rem}
          .code{background:#f0f4f8;border-radius:8px;padding:.75rem 1rem;font-size:.8rem;color:#dc2626;font-family:monospace;margin-bottom:1.5rem;text-align:left}
          a{display:inline-block;background:#2563eb;color:#fff;padding:.65rem 1.5rem;border-radius:8px;text-decoration:none;font-size:.9rem;font-weight:600}
        </style></head><body>
        <div class="box">
          <div class="icon">⚡</div>
          <h2>Database Connection Failed</h2>
          <p>IdeaMarket cannot connect to the database right now. Please check your configuration in <strong>includes/db.php</strong> and ensure MySQL is running.</p>
          <div class="code">
            Host: ' . DB_HOST . '<br>
            Database: ' . DB_NAME . '<br>
            User: ' . DB_USER . '<br><br>
            Error: ' . htmlspecialchars($e->getMessage()) . '
          </div>
          <a href="javascript:location.reload()">Try Again</a>
        </div>
        </body></html>';
    }
    exit;
}
