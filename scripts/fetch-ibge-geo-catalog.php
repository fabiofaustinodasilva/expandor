<?php

declare(strict_types=1);

$dir = dirname(__DIR__).'/database/data/geo';
if (! is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$statesRaw = file_get_contents('https://servicodados.ibge.gov.br/api/v1/localidades/estados?orderBy=nome');
$states = json_decode((string) $statesRaw, true);
if (! is_array($states)) {
    fwrite(STDERR, "Failed to fetch states\n");
    exit(1);
}

$stateRows = array_map(static fn (array $s): array => [
    'id' => (int) $s['id'],
    'uf' => (string) $s['sigla'],
    'name' => (string) $s['nome'],
], $states);

file_put_contents(
    $dir.'/states.json',
    json_encode($stateRows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);
echo 'states: '.count($stateRows).PHP_EOL;

$munRaw = file_get_contents('https://servicodados.ibge.gov.br/api/v1/localidades/municipios?orderBy=nome');
$mun = json_decode((string) $munRaw, true);
if (! is_array($mun)) {
    fwrite(STDERR, "Failed to fetch municipalities\n");
    exit(1);
}

$rows = [];
foreach ($mun as $m) {
    $uf = $m['microrregiao']['mesorregiao']['UF']['sigla']
        ?? $m['regiao-imediata']['regiao-intermediaria']['UF']['sigla']
        ?? null;
    if (! is_string($uf) || $uf === '') {
        continue;
    }
    $rows[] = [
        'ibge_code' => (string) $m['id'],
        'name' => (string) $m['nome'],
        'uf' => $uf,
    ];
}

file_put_contents(
    $dir.'/municipalities.json',
    json_encode($rows, JSON_UNESCAPED_UNICODE)
);
echo 'municipalities: '.count($rows).PHP_EOL;
