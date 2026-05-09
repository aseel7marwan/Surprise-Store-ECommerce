<?php
/**
 * Surprise Store — load environment variables from project root `.env`
 * (No Composer; safe for shared hosting.)
 */

/**
 * @param string $path Absolute path to .env
 */
function surprise_load_dotenv($path) {
    if (!is_readable($path)) {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            fwrite(STDERR, "Surprise Store: missing or unreadable .env at {$path}\nCopy .env.example to .env and configure.\n");
            exit(1);
        }
        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Configuration</title></head><body>';
        echo '<h1>Service unavailable</h1>';
        echo '<p>The application is not configured: the <code>.env</code> file is missing or unreadable.</p>';
        echo '<p>Copy <code>.env.example</code> to <code>.env</code> in the project root and set database and API values.</p>';
        echo '</body></html>';
        exit(1);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            fwrite(STDERR, "Surprise Store: could not read .env\n");
            exit(1);
        }
        http_response_code(503);
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Configuration error: could not read .env file.\n";
        exit(1);
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $eq = strpos($line, '=');
        if ($eq === false) {
            continue;
        }
        $name = trim(substr($line, 0, $eq));
        $value = trim(substr($line, $eq + 1));
        if ($name === '') {
            continue;
        }
        if (strlen($value) >= 2) {
            $q = $value[0];
            if (($q === '"' || $q === "'") && substr($value, -1) === $q) {
                $value = stripcslashes(substr($value, 1, -1));
            }
        }
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
    }
}

/**
 * Read env with optional default (empty string means “use default”).
 */
function surprise_env($key, $default = '') {
    if (array_key_exists($key, $_ENV)) {
        $v = $_ENV[$key];
        return ($v !== null && $v !== '') ? $v : $default;
    }
    $v = getenv($key);
    if ($v !== false && $v !== '') {
        return $v;
    }
    return $default;
}
