<?php

$GLOBALS['OFFSETS'] = [[0, 1], [0, -1], [1, 0], [-1, 0], [-1, 1], [1, -1]];

function isNeighbour($a, $b): bool {
    $a = explode(',', $a);
    $b = explode(',', $b);
    if ($a[0] == $b[0] && abs($a[1] - $b[1]) == 1) {
        return true;
    }
    if ($a[1] == $b[1] && abs($a[0] - $b[0]) == 1) {
        return true;
    }
    if ($a[0] + $a[1] == $b[0] + $b[1]) {
        return true;
    }
    return false;
}

function hasNeighbour($a, $board): bool
{
    foreach (array_keys($board) as $b) {
        if (isNeighbour($a, $b)) return true;
    }
    return false;
}

function isLegalPosition($player, $pos, $board): bool {
    if (count($board) == 1) {
        return true;
    } else {
        return neighboursAreSameColor($player, $pos, $board);
    }
}

function neighboursAreSameColor($player, $pos, $board): bool {
    foreach ($board as $b => $st) {
        if (!$st)  {
            continue;
        }
        $c = $st[count($st) - 1][0];
        if ($c != $player && isNeighbour($pos, $b)) {
            return false;
        }
    }
    return true;
}

function playerOwnsTile($board, $player, $from): bool {
    return $board[$from][count($board[$from])-1][0] == $player;
}

function len($tile): int {
    return $tile ? count($tile) : 0;
}

function slide($board, $from, $to): bool {
    if (!hasNeighbour($to, $board)) {
        return false;
    }
    if (!isNeighbour($from, $to))  {
        return false;
    }
    $b = explode(',', $to);
    $common = [];
    foreach ($GLOBALS['OFFSETS'] as $pq) {
        $p = $b[0] + $pq[0];
        $q = $b[1] + $pq[1];
        if (isNeighbour($from, $p.",".$q)) $common[] = $p.",".$q;
    }
    if (!isset($board[$common[0]]) && !isset($board[$common[1]]) && !isset($board[$from]) && !isset($board[$to])) {
        return false;
    }
    return min(len($board[$common[0]]), len($board[$common[1]])) <= max(len($board[$from]), len($board[$to]));
}