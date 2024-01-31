<?php

namespace Database;
use mysqli;

class Database {
    private mysqli $database;

    public function __construct() {
        $this->database = new mysqli('db', 'root', 'Incognito153!', 'hive');
    }

    public function getState(): string {
        return serialize([$_SESSION['hand'], $_SESSION['board'], $_SESSION['player']]);
    }

    public function undo(): void {
        $lastMove = $this->getLastMove();

        $stmt = $this->database->prepare('SELECT * FROM moves WHERE id = '.$lastMove);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_array();
        $this->setLastMove($result[5]);
        $this->setState($result[6]);
    }

    public function setState($state): void {
        list($a, $b, $c) = unserialize($state);
        $_SESSION['hand'] = $a;
        $_SESSION['board'] = $b;
        $_SESSION['player'] = $c;
    }

    public function getGameId() {
        return $_SESSION['game_id'] ?? null;
    }

    public function printMoves(): void {
        $gameId = $this->getGameId();
        $stmt = $this->database->prepare('SELECT * FROM moves WHERE game_id = '.$gameId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_array()) {
            echo '<li>'.$row[2].' '.$row[3].' '.$row[4].'</li>';
        }
    }

    public function getLastMove() {
        return $_SESSION['last_move'] ?? null;
    }

    public function setLastMove($move): void {
        $_SESSION['last_move'] = $move;
    }

    public function passTurn(): void {
        $state = $this->getState();
        $gameId = $this->getGameId();
        $lastMove = $this->getLastMove();

        $stmt = $this->database->prepare('insert into moves (game_id, type, move_from, move_to, previous_id, state) values (?, "pass", null, null, ?, ?)');
        $stmt->bind_param('iis', $gameId, $lastMove, $state);
        $stmt->execute();
    }

    public function movePiece($from, $to): void {
        $state = $this->getState();
        $gameId = $this->getGameId();
        $lastMove = $this->getLastMove();

        $stmt = $this->database->prepare('insert into moves (game_id, type, move_from, move_to, previous_id, state) values (?, "move", ?, ?, ?, ?)');
        $stmt->bind_param('issis', $gameId, $from, $to, $lastMove, $state);
        $stmt->execute();
    }

    public function playPiece($to, $piece): void {
        $state = $this->getState();
        $gameId = $this->getGameId();
        $lastMove = $this->getLastMove();

        $stmt = $this->database->prepare('insert into moves (game_id, type, move_from, move_to, previous_id, state) values (?, "play", ?, ?, ?, ?)');
        $stmt->bind_param('issis', $gameId, $to, $piece, $lastMove, $state);
        $stmt->execute();
    }

    public function getInsertID(): int|string {
        return $this->database->insert_id;
    }

    public function setGameId($gameId): void {
        $_SESSION['game_id'] = $gameId;
    }

    public function restartGame(): void {
        $_SESSION['board'] = [];
        $_SESSION['hand'] = [0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3], 1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]];
        $_SESSION['player'] = 0;

        unset($_SESSION['error']);
        unset($_SESSION['last_move']);

        $this->database->prepare('INSERT INTO games VALUES ()')->execute();

        $this->setGameId($this->getInsertID());

    }
}