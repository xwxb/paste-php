<!DOCTYPE html>
<html>
<head>
    <title>Paste <?php echo htmlspecialchars($uuid); ?></title>
    <link rel="stylesheet" href="/web/style.css">
</head>
<body>
    <pre><code><?php
    foreach ($lines as $index => $line) {
        echo '<span class="line-numbers">' . str_pad($index + 1, 3, ' ', STR_PAD_LEFT) . '</span>' . $line . "\n";
    }
    ?></code></pre>
    <div class="controls">
        <a href="/<?php echo htmlspecialchars($uuid); ?>/edit" class="button">Edit</a>
    </div>
</body>
</html>
