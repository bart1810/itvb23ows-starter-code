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

    public function setState($state): void {
        list($a, $b, $c) = unserialize($state);
        $_SESSION['hand'] = $a;
        $_SESSION['board'] = $b;
        $_SESSION['player'] = $c;
    }

    public function movePiece($from, $to, $gameId, $lastMove): void {
        $state = $this->getState();
        $stmt = $this->database->prepare('insert into moves (game_id, type, move_from, move_to, previous_id, state) values (?, "move", ?, ?, ?, ?)');
        $stmt->bind_param('issis', $gameId, $from, $to, $lastMove, $state);
        $stmt->execute();
    }

    public function playPiece($to, $piece, $gameId, $lastMove): void {
        $state = $this->getState();
        $stmt = $this->database->prepare('insert into moves (game_id, type, move_from, move_to, previous_id, state) values (?, "play", ?, ?, ?, ?)');
        $stmt->bind_param('issis', $gameId, $piece, $to, $lastMove, $state);
        $stmt->execute();
    }

    public function getInsertID(): int|string {
        return $this->database->insert_id;
    }

    public function restartGame(): void {
        $_SESSION['board'] = [];
        $_SESSION['hand'] = [0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3], 1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]];
        $_SESSION['player'] = 0;

        unset($_SESSION['error']);
        unset($_SESSION['last_move']);

        $this->database->prepare('INSERT INTO games VALUES ()')->execute();
    }
}