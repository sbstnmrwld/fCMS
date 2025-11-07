<?php
/**
 * fCMS Passwort-Hash-Generator
 *
 * SICHERHEITSHINWEIS: Löschen Sie diese Datei nach dem ersten Gebrauch!
 */

$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if ($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort-Hash-Generator - fCMS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            color: #856404;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="password"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        textarea {
            font-family: monospace;
            resize: vertical;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #0056b3;
        }
        .result {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            word-break: break-all;
        }
        .instructions {
            margin-top: 20px;
            padding: 15px;
            background: #e7f3ff;
            border-radius: 4px;
            font-size: 14px;
        }
        .instructions ol {
            margin: 10px 0;
            padding-left: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Passwort-Hash-Generator</h1>

        <div class="warning">
            <strong>⚠️ Sicherheitshinweis:</strong> Löschen Sie diese Datei nach dem Generieren Ihres Passwort-Hashes!
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="password">Ihr gewünschtes Passwort:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit">Hash generieren</button>
        </form>

        <?php if (isset($hash)): ?>
        <div class="result">
            <strong>Ihr Passwort-Hash:</strong><br>
            <textarea rows="3" readonly onclick="this.select()"><?= htmlspecialchars($hash) ?></textarea>
        </div>

        <div class="instructions">
            <strong>So verwenden Sie den Hash:</strong>
            <ol>
                <li>Kopieren Sie den generierten Hash (klicken Sie in das Textfeld und drücken Sie Strg+C)</li>
                <li>Öffnen Sie die Datei <code>config/config.php</code></li>
                <li>Suchen Sie die Zeile mit <code>'password' => '...'</code></li>
                <li>Ersetzen Sie den alten Hash durch Ihren neuen Hash</li>
                <li>Speichern Sie die Datei</li>
                <li><strong>Löschen Sie diese Datei (generate-password.php)</strong></li>
            </ol>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
