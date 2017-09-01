<?php

function shell(string $cmd, string $stdin = "", &$std = []): string
{
    $descriptorspec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    if ($stdin) {
        $descriptorspec[0] = ['pipe', 'r'];
    }

    $process = proc_open($cmd, $descriptorspec, $pipes);

    if (!is_resource($process)) {
        throw new \Exception($cmd);
    }

    if ($stdin) {
        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    proc_close($process);

    $std[1] = $stdout;
    $std[2] = $stderr;

    fputs(STDERR, $stderr);

    return $stdout;
}