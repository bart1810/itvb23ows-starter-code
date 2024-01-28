<?php

function getState(): string {
    return serialize([$_SESSION['hand'], $_SESSION['board'], $_SESSION['player']]);
}

function setState($state): void {
    list($a, $b, $c) = unserialize($state);
    $_SESSION['hand'] = $a;
    $_SESSION['board'] = $b;
    $_SESSION['player'] = $c;
}

return new mysqli('db', 'root', 'Incognito153!', 'hive');