<?php
declare(strict_types=1);

/**
 * Minimal .env loader (KEY=value, # comments, optional quotes).
 *
 * Values are kept in a PHP array rather than pushed into the process with
 * putenv(), because many shared hosts (InfinityFree, iFastNet, some cPanel
 * plans) disable putenv()/getenv(). Real environment variables still win when
 * the host provides them, so production servers can inject secrets without a
 * .env file on disk.
 */
function env_store(?array $set = null): array
{
    static $values = [];
    if ($set !== null) {
        $values = $set;
    }
    return $values;
}

function load_env(string $path): bool
{
    if (!is_file($path) || !is_readable($path)) {
        return false;
    }
    $values = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim(preg_replace('/^\xEF\xBB\xBF/', '', $line));   // strip a UTF-8 BOM added by Notepad
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $quote = $value[0];
            $end = strpos($value, $quote, 1);
            $value = $end === false ? substr($value, 1) : substr($value, 1, $end - 1);
        } else {
            $value = trim((string) preg_replace('/\s+#.*$/', '', $value));
        }
        $values[$key] = $value;
    }
    env_store($values);
    return true;
}

function env(string $key, ?string $default = null): ?string
{
    // 1. A real environment variable, if the host sets one and getenv() is allowed.
    if (function_exists('getenv')) {
        $real = @getenv($key);
        if (is_string($real) && $real !== '') {
            return $real;
        }
    }
    if (isset($_SERVER[$key]) && is_string($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return $_SERVER[$key];   // e.g. Apache SetEnv
    }
    // 2. The .env file.
    $value = env_store()[$key] ?? null;
    return $value === null || $value === '' ? $default : $value;
}
