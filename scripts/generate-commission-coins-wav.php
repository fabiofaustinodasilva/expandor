<?php

declare(strict_types=1);

$rate = 22050;
$duration = 1.35;
$n = (int) ($rate * $duration);
$samples = array_fill(0, $n, 0.0);

$hits = [
    ['t' => 0.00, 'f' => 2650, 'amp' => 0.55, 'decay' => 28],
    ['t' => 0.07, 'f' => 3180, 'amp' => 0.48, 'decay' => 32],
    ['t' => 0.14, 'f' => 2420, 'amp' => 0.42, 'decay' => 30],
    ['t' => 0.23, 'f' => 3560, 'amp' => 0.38, 'decay' => 34],
    ['t' => 0.31, 'f' => 2890, 'amp' => 0.36, 'decay' => 31],
    ['t' => 0.42, 'f' => 4100, 'amp' => 0.28, 'decay' => 36],
    ['t' => 0.55, 'f' => 2750, 'amp' => 0.22, 'decay' => 26],
    ['t' => 0.70, 'f' => 3350, 'amp' => 0.18, 'decay' => 30],
    ['t' => 0.88, 'f' => 2500, 'amp' => 0.12, 'decay' => 24],
];

foreach ($hits as $hit) {
    $start = (int) ($hit['t'] * $rate);
    for ($i = 0; $i < (int) (0.18 * $rate); $i++) {
        $idx = $start + $i;
        if ($idx >= $n) {
            break;
        }
        $t = $i / $rate;
        $env = exp(-$hit['decay'] * $t);
        $tone = sin(2 * M_PI * $hit['f'] * $t)
            + 0.45 * sin(2 * M_PI * $hit['f'] * 1.53 * $t)
            + 0.25 * sin(2 * M_PI * $hit['f'] * 2.17 * $t);
        $noise = ((mt_rand() / mt_getrandmax()) * 2 - 1) * exp(-90 * $t);
        $samples[$idx] += $hit['amp'] * $env * ($tone * 0.55 + $noise * 0.35);
    }
}

for ($i = 0; $i < $n; $i++) {
    $t = $i / $rate;
    $samples[$i] += sin(2 * M_PI * 180 * $t) * exp(-3.2 * $t) * 0.04;
}

$peak = 0.0001;
foreach ($samples as $v) {
    $peak = max($peak, abs($v));
}
$gain = 0.72 / $peak;

$pcm = '';
foreach ($samples as $v) {
    $x = max(-1.0, min(1.0, $v * $gain));
    $pcm .= pack('v', (int) round($x * 32767));
}

$dataSize = strlen($pcm);
$fmt = pack('v*', 1, 1).pack('V*', $rate, $rate * 2).pack('v*', 2, 16);
$chunks = 'fmt '.pack('V', strlen($fmt)).$fmt.'data'.pack('V', $dataSize).$pcm;
$wav = 'RIFF'.pack('V', 36 + $dataSize).'WAVE'.$chunks;

$path = 'C:\\Users\\Fabio Faustino\\Desktop\\GeoSales-CRM\\public\\sounds\\commission-coins.wav';
file_put_contents($path, $wav);
fwrite(STDOUT, $path.' '.filesize($path).PHP_EOL);
