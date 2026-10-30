<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host  = getenv('DB_HOST') ?: 'db';
$name  = getenv('DB_NAME') ?: 'notesdb';
$user  = getenv('DB_USER') ?: 'notesuser';
$pass  = getenv('DB_PASS') ?: 'notespass123';
$label = getenv('APP_LABEL') ?: 'Part I (docker-compose on EC2)';

$error = null;
$notes = [];
$dbVersion = '';

try {
    $db = new mysqli($host, $user, $pass, $name);
    $dbVersion = $db->server_info;

    $db->query('CREATE TABLE IF NOT EXISTS notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        author VARCHAR(100) NOT NULL,
        message VARCHAR(500) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $author  = mb_substr(trim($_POST['author'] ?? ''), 0, 100);
        $message = mb_substr(trim($_POST['message'] ?? ''), 0, 500);
        if ($author !== '' && $message !== '') {
            $stmt = $db->prepare('INSERT INTO notes (author, message) VALUES (?, ?)');
            $stmt->bind_param('ss', $author, $message);
            $stmt->execute();
        }
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }

    $result = $db->query('SELECT author, message, created_at FROM notes ORDER BY id DESC LIMIT 20');
    while ($row = $result->fetch_assoc()) {
        $notes[] = $row;
    }
} catch (mysqli_sql_exception $e) {
    $error = 'The database is not reachable yet. Please refresh in a few seconds.';
}

function h($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notes App</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 30px auto; padding: 0 15px; color: #222; }
        h1 { margin-bottom: 4px; }
        .tag { color: #555; margin-top: 0; }
        .ok { color: #0a7d2c; }
        .bad { color: #b00020; }
        form { margin: 20px 0; }
        input, textarea { width: 100%; padding: 8px; margin: 5px 0 10px; box-sizing: border-box; }
        button { padding: 8px 18px; cursor: pointer; }
        .note { border: 1px solid #ccc; border-radius: 4px; padding: 10px; margin-bottom: 10px; }
        .meta { color: #666; font-size: 0.85em; }
    </style>
</head>
<body>
    <h1>Notes App</h1>
    <p class="tag">Deployment: <strong><?= h($label) ?></strong></p>

    <?php if ($error): ?>
        <p class="bad"><?= h($error) ?></p>
    <?php else: ?>
        <p class="ok">Database status: connected (MariaDB <?= h($dbVersion) ?>)</p>

        <form method="post">
            <label>Your name</label>
            <input type="text" name="author" maxlength="100" required>
            <label>Note</label>
            <textarea name="message" rows="3" maxlength="500" required></textarea>
            <button type="submit">Save note</button>
        </form>

        <h2>Saved notes (<?= count($notes) ?>)</h2>
        <?php foreach ($notes as $n): ?>
            <div class="note">
                <?= h($n['message']) ?>
                <div class="meta">by <?= h($n['author']) ?> on <?= h($n['created_at']) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
