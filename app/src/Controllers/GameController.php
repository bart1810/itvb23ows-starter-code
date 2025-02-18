<?php

namespace Controllers;

use Database\Database;

class GameController {
    private array $offsets = [[0, 1], [0, -1], [1, 0], [-1, 0], [-1, 1], [1, -1]];

    private Database $database;
    private ErrorController $errorController;
    private PlayerController $playerController;
    public function __construct(Database $database, PlayerController $playerController) {
        $this->database = $database;
        $this->errorController = new ErrorController();
        $this->playerController = $playerController;
    }

    public function getOffsets(): array {
        return $this->offsets;
    }

    public function getBoard() {
        return $_SESSION['board'] ?? null;
    }

    public function setBoard($board): void {
        $_SESSION['board'] = $board;
    }

    public function getToPositions(): array {
        $to = [];
        foreach ($this->getOffsets() as $pq) {
            foreach (array_keys($this->getBoard()) as $pos) {
                $pq2 = explode(',', $pos);
                $to[] = ($pq[0] + $pq2[0]).','.($pq[1] + $pq2[1]);
            }
        }
        $to = array_unique($to);
        if (!count($to)) {
            $to[] = '0,0';
        }

        return $to;
    }

//  Helper function for tests
    public function currentPlayerPlayerPositions(): array {
        $player = $this->playerController->getPlayer();
        $board = $this->getBoard();

        $positions = [];

        foreach ($board as $pos => $tiles) {
            if (end($tiles)[0] == $player) {
                $positions[] = $pos;
            }
        }
        return $positions;
    }

    public function playerOwnsTile($board, $player, $from): bool {
        return $board[$from][count($board[$from])-1][0] == $player;
    }

    public function endOfGameFor($player): bool {
        $board = $this->getBoard();

        foreach ($board as $pos => $tiles) {
            $endTile = end($tiles);

            $neighbours = [];
            $b = explode(',', $pos);

            foreach ($this->offsets as $pq) {
                $p = $b[0] + $pq[0];
                $q = $b[1] + $pq[1];

                $position = $p.",".$q;

                if (isset($board[$position]) && $this->isNeighbour($pos, $position)) {
                    $neighbours[] = $position;
                }
            }

            if ($endTile[0] == $player && $endTile[1] == 'Q' && count($neighbours) == 6) {
                return true;
            }
        }
        return false;
    }


    public function pass(): void {
        $player = $this->playerController->getPlayer();
        $deck = $this->playerController->getDeck()[$player];

        foreach ($this->getToPositions() as $to) {
            foreach ($deck as $piece => $amount) {
                if ($amount > 0 && $this->isValidPlay($piece, $to)) {
                    $this->errorController->setError("There is a play possible, can't pass");
                    return;
                }
            }
        }
        foreach ($this->getBoard() as $pos => $tiles) {
            $topTile = end($tiles);

            if ($topTile[0] == $player) {
                foreach ($this->getToPositions() as $to) {
                    if ($this->isValidMove($pos, $to)) {
                        $this->errorController->setError("There is a move possible, can't pass");
                        return;
                    }
                }
            }
        }
        $this->database->movePiece(null, null);
        $this->playerController->switchPlayer();
    }

    public function playPiece($piece, $to): void {
        if ($this->isValidPlay($piece, $to)) {

            $player = $this->playerController->getPlayer();

            unset($_SESSION['ERROR']);

            $_SESSION['board'][$to] = [[$_SESSION['player'], $piece]];
            $_SESSION['hand'][$player][$piece]--;

            $this->database->playPiece($to, $piece);

            $this->playerController->switchPlayer();

            $this->database->setLastMove($this->database->getInsertID());
        }
    }

    public function movePiece($from, $to): void {
        if ($this->isValidMove($from, $to)) {

            $board = $this->getBoard();

            unset($_SESSION['ERROR']);

            $tile = array_pop($board[$from]);

            unset($board[$from]);
            $board[$to] = [$tile];
            $this->setBoard($board);

            $this->database->movePiece($from, $to);

            $this->playerController->switchPlayer();

            $this->database->setLastMove($this->database->getInsertID());
        }
    }

    private function isValidPlay($piece, $to): bool {
        $player = $this->playerController->getPlayer();
        $deck = $this->playerController->getDeck()[$player];
        $board = $this->getBoard();

        if (!$deck[$piece]) {
            $this->errorController->setError("Player does not have tile");
        } elseif (isset($board[$to])) {
            $this->errorController->setError('Board position is not empty');
        } elseif (count($board) && !$this->hasNeighbour($to, $board)) {
            $this->errorController->setError("Board position has no neighbour");
        } elseif (array_sum($deck) < 11 && !$this->neighboursAreSameColor($to)) {
            $this->errorController->setError("Board position has opposing neighbour");
        } elseif ($piece != 'Q' && array_sum($deck) <= 8 && $deck['Q']) {
            $this->errorController->setError('Must play queen bee');
        } else {
            return true;
        }
        return false;
    }

    private function isValidMove($from, $to): bool
    {
        $player = $this->playerController->getPlayer();
        $deck = $this->playerController->getDeck()[$player];
        $board = $this->getBoard();

        unset($_SESSION['ERROR']);

        if (!isset($board[$from])) {
            $this->errorController->setError("Board position is empty");
        }
        elseif ($from == $to) {
            $this->errorController->setError("Tile must move");
        }
        elseif (!$this->playerOwnsTile($board, $player, $from)) {
            $this->errorController->setError("Tile is not owned by player");
        }
        elseif ($deck['Q']) {
            $this->errorController->setError("Queen bee is not played");
        }
        else {
            $tile = array_pop($board[$from]);
            unset($board[$from]);

            if (!$this->hasNeighbour($to, $board) || $this->isNotAttachedToHive()) {
                $this->errorController->setError("Move would split hive");
            }
            elseif (isset($board[$to]) && $tile[1] != "B") {
                $this->errorController->setError("Tile not empty");
            }
            elseif ((($tile[1] == "Q" || $tile[1] == "B") && !$this->slide($from, $to)) || ($tile[1] == "G" && !$this->slideForGrasshopper($from, $to)) ||
                ($tile[1] == "A" && !$this->slideForAntSoldier($from, $to)) || $tile[1] == 'S' && !$this->slideForSpider($from, $to)) {
                $this->errorController->setError("Tile must slide");
            } else {
                return true;
            }
        }
        return false;
    }

    private function isNotAttachedToHive(): array {
        $board = $this->getBoard();

        $all = array_keys($board);
        $queue = [array_shift($all)];

        while ($queue) {
            $next = explode(',', array_shift($queue));
            foreach ($this->offsets as $pq) {
                list($p, $q) = $pq;
                $p += $next[0];
                $q += $next[1];

                if (in_array("$p,$q", $all)) {
                    $queue[] = "$p,$q";
                    $all = array_diff($all, ["$p,$q"]);
                }
            }
        }
        return $all;
    }

    private function isNeighbour($a, $b): bool {
        $a = explode(',', $a);
        $b = explode(',', $b);

        if ($a[0] == $b[0] && abs($a[1] - $b[1]) == 1 || $a[1] == $b[1] &&
            abs($a[0] - $b[0]) == 1 || $a[0] + $a[1] == $b[0] + $b[1]) {
            return true;
        }

        return false;
    }

    private function hasNeighbour($a, $board): bool {
        $b = explode(',', $a);

        foreach ($this->offsets as $pq) {
            $p = $b[0] + $pq[0];
            $q = $b[1] + $pq[1];

            $position = $p . "," . $q;

            if (isset($board[$position]) &&
                $this->isNeighbour($a, $position)
            ) {
                return true;
            }
        }
        return false;
    }

    private function neighboursAreSameColor($a): bool {
        $player = $this->playerController->getPlayer();
        $board = $this->getBoard();

        foreach ($board as $b => $st) {
            if (!$st) {
                continue;
            }
            $c = $st[count($st) - 1][0];
            if ($c != $player && $this->isNeighbour($a, $b)) {
                return false;
            }
        }
        return true;
    }

    private function len($tile): int {
        return $tile ? count($tile) : 0;
    }

    private function slide($from, $to): bool {
        $board = $this->getBoard();

        if (!$this->hasNeighbour($to, $board) || !$this->isNeighbour($from, $to)) {
            return false;
        }

        $b = explode(',', $to);

        $common = [];
        foreach ($this->offsets as $pq) {
            $p = $b[0] + $pq[0];
            $q = $b[1] + $pq[1];
            if ($this->isNeighbour($from, $p.",".$q)) {
                $common[] = $p.",".$q;
            }
        }

        if ((!isset($board[$common[0]]) || !$board[$common[0]]) && (!isset($board[$common[1]]) || !$board[$common[1]]) &&
            (!isset($board[$from]) || !$board[$from]) && (!isset($board[$to]) || !$board[$to])) {
            return false;
        }

        return min($this->len($board[$common[0]] ?? 0), $this->len($board[$common[1]] ?? 0))
            <= max($this->len($board[$from] ?? 0), $this->len($board[$to] ?? 0));
    }

    public function slideForGrasshopper($from, $to): bool {
        $board = $this->getBoard();

        if ($from == $to) {
            $this->errorController->setError("Grasshopper can't jump to the same place as he is standing on");
            return false;
        }

        $a = explode(',', $from);
        $b = explode(',', $to);

        $jumpDirection = $this->getJumpDirection($a, $b);

        if ($jumpDirection == null) {
            return false;
        }

        $p = $a[0] + $jumpDirection[0];
        $q = $a[1] + $jumpDirection[1];

        $pos = $p.",".$q;

        if (!isset($board[$pos])) {
            return false;
        }

        while (isset($board[$pos])) {
            $p += $jumpDirection[0];
            $q += $jumpDirection[1];

            $pos = $p . "," . $q;
        }

        if ($pos == $to) {
            return true;
        }

        return false;
    }

    public function slideForAntSoldier($from, $to): bool {
        $board = $this->getBoard();

        if ($from == $to) {
            $this->errorController->setError("Soldier ant can't slide on the same place");
            return false;
        }

        foreach(array_keys($board) as $b) {
            if ($this->hasNeighbour($to, $b)) {
                continue;
            } else {
                return false;
            }
        }
        unset($board[$from]);

        $visited = [];
        $tiles = array($from);

        while (!empty($tiles)) {
            $currentTile = array_shift($tiles);

            if (!in_array($currentTile, $visited)) {
                $visited[] = $currentTile;
            }

            $b = explode(',', $currentTile);

            foreach ($this->offsets as $pq) {
                $p = $b[0] + $pq[0];
                $q = $b[1] + $pq[1];

                $pos = $p.",".$q;

                if (!in_array($pos, $visited) && !isset($board[$pos]) && $this->hasNeighbour($pos, $board)) {
                    if ($pos == $to) {
                        return true;
                    }
                    $tiles[] = $pos;
                }
            }
        }
        return false;
    }

    public function slideForSpider($from, $to): bool {
        $board = $this->getBoard();
        unset($board[$from]);
        if ($from == $to) {
            return false;
        }

        $visited = [];
        $tiles = array($from);
        $tiles[] = null;

        $previousTile = null;
        $takenSteps = 0;

        while (!empty($tiles) && $takenSteps < 3) {
            $currentTile = array_shift($tiles);

            if ($currentTile == null) {
                $takenSteps++;
                $tiles[] = null;
                if (reset($tiles) == null) {
                    break;
                } else {
                    continue;
                }
            }

            if (!in_array($currentTile, $visited)) {
                $visited[] = $currentTile;
            }

            $b = explode(',', $currentTile);

            foreach ($this->offsets as $pq) {
                $p = $b[0] + $pq[0];
                $q = $b[1] + $pq[1];

                $pos = $p.",".$q;

                if (!in_array($pos, $visited) && $pos != $previousTile && !isset($board[$pos]) && $this->hasNeighbour($pos, $board)) {
//                  variable takenSteps is two because it starts at 0
                    if ($pos == $to && $takenSteps == 2) {
                        return true;
                    }
                    $tiles[] = $pos;
                }
            }

            $previousTile = $currentTile;
        }

        return false;
    }

    private function getJumpDirection($a, $b): array | null {

        if ($a[0] == $b[0]) {
            return $b[1] > $a[1] ? [0, 1] : [0, -1];
        }
        elseif ($a[1] == $b[1]) {
            return $b[0] > $a[0] ? [1, 0] : [-1, 0];
        }
        elseif (abs($a[0] - $a[1]) == abs($b[0] - $b[1])) {
            return $b[1] > $a[1] ? [-1, 1] : [1, -1];
        }
        else {
            return null;
        }
    }
}