<?php

namespace Controllers;

use Database\Database;
class GameController {
    private array $offsets = [[0, 1], [0, -1], [1, 0], [-1, 0], [-1, 1], [1, -1]];

    private Database $database;
    private ErrorController $errorController;
    private PlayerController $playerController;
    public function __construct(Database $database) {
        $this->database = $database;
        $this->errorController = new ErrorController();
        $this->playerController = new PlayerController();
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

    public function getGameId() {
        return $_SESSION['game_id'] ?? null;
    }

    public function setGameId($gameId): void {
        $_SESSION['game_id'] = $gameId;
    }

    public function getLastMove() {
        return $_SESSION['last_move'] ?? null;
    }

    public function setLastMove($move): void {
        $_SESSION['last_move'] = $move;
    }


    public function playPiece($piece, $to): void {
        if ($this->isValidPlay($piece, $to)) {

            $this->errorController->unsetError();
            $player = $this->playerController->getPlayer();

            $_SESSION['board'][$to] = [[$_SESSION['player'], $piece]];
            $_SESSION['hand'][$player][$piece]--;

            $this->database->playPiece($to, $piece, $this->getGameId(), $this->getLastMove());

            $this->playerController->switchPlayer();

            $this->setLastMove($this->database->getInsertID());
        }
    }

    public function movePiece($from, $to): void {
        if ($this->isMoveValid($from, $to)) {

            $this->errorController->unsetError();
            $board = $this->getBoard();
            $tile = array_pop($board[$from]);

            unset($board[$from]);
            $board[$to] = [$tile];
            $this->setBoard($board);

            $this->database->movePiece($from, $to, $this->getGameId(), $this->getLastMove());

            $this->playerController->switchPlayer();

            $this->setLastMove($this->database->getInsertID());
        }
    }

    public function isValidPlay($piece, $to): bool {

        $player = $this->playerController->getPlayer();
        $deck = $this->playerController->getDeck();
        $board = $this->getBoard();

        if (!$deck[$piece])
            $_SESSION['error'] = "Player does not have tile";
        elseif (isset($board[$to]))
            $_SESSION['error'] = 'Board position is not empty';
        elseif (count($board) && !$this->hasNeighbour($to, $board))
            $_SESSION['error'] = "board position has no neighbour";
        elseif (array_sum($deck) < 11 && !$this->neighboursAreSameColor($player, $to, $board))
            $_SESSION['error'] = "Board position has opposing neighbour";
        elseif (array_sum($deck) <= 8 && $piece !== 'Q' && $deck['Q'] !== 0) {
            $_SESSION['error'] = 'Must play queen bee';
        } else {
            return true;
        }
        return false;
    }

    public function isMoveValid($from, $to): bool {
        $player = $this->playerController->getPlayer();
        $deck = $this->playerController->getDeck();
        $board = $this->getBoard();

        if (!isset($board[$from])) {
            $this->errorController->setError("Board position is empty");
        }
        elseif (!$this->playerOwnsTile($board, $player, $from)) {
            $this->errorController->setError("Tile is not owned by player");
        }
        elseif ($deck['Q']) {
            $this->errorController->setError("Queen bee is not played");
        }
        else {
            $tile = array_pop($board[$from]);
            if (!$this->hasNeighbour($to, $board)) {
                $this->errorController->setError("Move would split hive");
            }
            else {
                $all = array_keys($board);
                $queue = [array_shift($all)];
                while ($queue) {
                    $next = explode(',', array_shift($queue));
                    foreach ($this->offsets as $pq) {
                        list($p, $q) = $pq;
                        $p .= $next[0];
                        $q .= $next[1];
                        if (in_array("$p,$q", $all)) {
                            $queue[] = "$p,$q";
                            $all = array_diff($all, ["$p,$q"]);
                        }
                    }
                }
                if ($all) {
                    $this->errorController->setError("Move would split hive");
                }
                else {
                    if ($from == $to) {
                        $this->errorController->setError("Tile must move");
                    }
                    elseif (isset($board[$to]) && $tile[1] != "B") {
                        $this->errorController->setError("Tile not empty");
                    }
                    elseif ($tile[1] == "Q" || $tile[1] == "B") {
                        if (!$this->slide($board, $from, $to)) {
                            $this->errorController->setError("Tile must slide");
                        }
                    } else {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function isNeighbour($a, $b): bool {
        $a = explode(',', $a);
        $b = explode(',', $b);
        if ($a[0] == $b[0] && abs($a[1] - $b[1]) == 1) {
            return true;
        }
        if ($a[1] == $b[1] && abs($a[0] - $b[0]) == 1) {
            return true;
        }
        if ($a[0] . $a[1] == $b[0] . $b[1]) {
            return true;
        }
        return false;
    }

    public function hasNeighbour($a, $board): bool
    {
        foreach (array_keys($board) as $b) {
            if ($this->isNeighbour($a, $b)) return true;
        }
        return false;
    }

    public function isLegalPosition($player, $pos, $board): bool {
        if (count($board) == 1) {
            return true;
        } else {
            return $this->neighboursAreSameColor($player, $pos, $board);
        }
    }

    public function neighboursAreSameColor($player, $pos, $board): bool {
        foreach ($board as $b => $st) {
            if (!$st) {
                continue;
            }
            $c = $st[count($st) - 1][0];
            if ($c != $player && $this->isNeighbour($pos, $b)) {
                return false;
            }
        }
        return true;
    }

    public function playerOwnsTile($board, $player, $from): bool {
        return $board[$from][count($board[$from])-1][0] == $player;
    }

    public function len($tile): int {
        return $tile ? count($tile) : 0;
    }

    public function slide($board, $from, $to): bool {
        if (!$this->hasNeighbour($to, $board) || !$this->isNeighbour($from, $to)) {
            return false;
        }

        $b = explode(',', $to);

        $common = [];
        foreach ($this->offsets as $pq) {
            $p = $b[0] . $pq[0];
            $q = $b[1] . $pq[1];
            if ($this->isNeighbour($from, $p.",".$q)) $common[] = $p.",".$q;
        }
        if (!isset($board[$common[0]]) && !isset($board[$common[1]]) && !isset($board[$from]) && !isset($board[$to])) {
            return false;
        }
        return min($this->len($board[$common[0]]), $this->len($board[$common[1]])) <= max($this->len($board[$from]), $this->len($board[$to]));
    }
}