<?php

declare(strict_types=1);

/**
 * make-payload.php
 *
 * Generate a base64url(JSON) payload for the FormPrefill plugin.
 *
 * Usage examples:
 *   php bin/make-payload.php --vars project=K8s --fields subject="Hello {{project}}" --fields message="Line1\nLine2"
 *   php bin/make-payload.php --out payload.txt --fields message=@message.txt
 *
 * Notes:
 * - Use @file to read value from a file.
 * - Newlines: literal \n will be converted to real newlines.
 */

function parseArgs(array $argv): array
{
    $out = ['vars' => [], 'fields' => [], 'outFile' => null];

    for ($i = 0; $i < count($argv); $i++) {
        $a = $argv[$i];

        if ($a === '--out') {
            $out['outFile'] = $argv[$i + 1] ?? null;
            $i++;
            continue;
        }

        if ($a === '--vars') {
            $kv = $argv[$i + 1] ?? '';
            [$k, $v] = parseKV($kv);
            $out['vars'][$k] = $v;
            $i++;
            continue;
        }

        if ($a === '--fields') {
            $kv = $argv[$i + 1] ?? '';
            [$k, $v] = parseKV($kv);
            $out['fields'][$k] = $v;
            $i++;
            continue;
        }
    }

    return $out;
}

function parseKV(string $s): array
{
    $pos = strpos($s, '=');
    if ($pos === false || $pos === 0) {
        throw new RuntimeException("Invalid key=value: {$s}");
    }

    $k = substr($s, 0, $pos);
    $v = substr($s, $pos + 1);

    // @file support
    if (str_starts_with($v, '@')) {
        $path = substr($v, 1);
        $v = file_get_contents($path);
        if ($v === false) {
            throw new RuntimeException("Failed to read file: {$path}");
        }
    }

    // Convert literal \n sequences to newlines
    $v = str_replace('\\n', "\n", $v);

    return [$k, $v];
}

function encodePayloadBase64Url(array $payload): string
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Failed to JSON-encode payload.');
    }

    $b64 = base64_encode($json);
    return rtrim(strtr($b64, '+/', '-_'), '=');
}

$args = parseArgs(array_slice($argv, 1));
$payload = ['vars' => (object)$args['vars'], 'fields' => (object)$args['fields']];
$enc = encodePayloadBase64Url($payload);

if (!empty($args['outFile'])) {
    file_put_contents($args['outFile'], $enc . PHP_EOL);
} else {
    echo $enc . PHP_EOL;
}
