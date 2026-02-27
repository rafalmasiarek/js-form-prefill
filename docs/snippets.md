# Snippets

## JavaScript: encode payload and build URL

```js
function encodePayloadBase64Url(obj) {
  const json = JSON.stringify(obj);
  const bytes = new TextEncoder().encode(json);

  let bin = '';
  for (const b of bytes) bin += String.fromCharCode(b);

  const b64 = btoa(bin);
  return b64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
}

function buildPrefillUrl(baseUrl, { flat = {}, vars = {}, payloadFields = {}, payloadVars = {} } = {}) {
  const p = new URLSearchParams();

  const hasPayload = Object.keys(payloadFields).length || Object.keys(payloadVars).length;
  if (hasPayload) {
    p.set('payload', encodePayloadBase64Url({ vars: payloadVars, fields: payloadFields }));
  }

  for (const [k, v] of Object.entries(vars)) p.set(`v_${k}`, String(v));
  for (const [k, v] of Object.entries(flat)) p.set(k, String(v));

  return baseUrl + (baseUrl.includes('?') ? '&' : '?') + p.toString();
}
```

## PHP: encode payload and build URL

```php
<?php

declare(strict_types=1);

function encode_payload_base64url(array $payload): string
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Failed to JSON-encode payload.');
    }

    $b64 = base64_encode($json);
    return rtrim(strtr($b64, '+/', '-_'), '=');
}

function build_prefill_url(string $baseUrl, array $flat = [], array $vars = [], array $payloadFields = [], array $payloadVars = []): string
{
    $query = [];

    if (!empty($payloadFields) || !empty($payloadVars)) {
        $payload = ['vars' => (object)$payloadVars, 'fields' => (object)$payloadFields];
        $query['payload'] = encode_payload_base64url($payload);
    }

    foreach ($vars as $k => $v) $query['v_' . $k] = (string)$v;
    foreach ($flat as $k => $v) $query[$k] = (string)$v;

    $sep = (str_contains($baseUrl, '?')) ? '&' : '?';
    return $baseUrl . $sep . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}
```

## CLI helpers

Generate payload with Node:

```bash
node bin/make-payload.js --vars project=K8s --fields subject="Hello {{project}}" --fields message="Line1\nLine2"
```

Generate payload with PHP:

```bash
php bin/make-payload.php --vars project=K8s --fields subject="Hello {{project}}" --fields message="Line1\nLine2"
```
