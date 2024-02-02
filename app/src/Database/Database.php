<?php

namespace Database;
use Controllers\PlayerController;
use mysqli;

class Database {
    private mysqli $database;
    private PlayerController $playerController;

    public function __construct() {
        $this->database = new mysqli('db', 'root', 'Incognito153!', 'hive');
        $this->playerController = new PlayerController();
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

    public function getGameId() {
        return $_SESSION['game_id'] ?? null;
    }

    public function getMove($moveId): false|\mysqli_result {
        $stmt = $this->database->prepare('SELECT * FROM moves WHERE id = ?');

        $stmt->bind_param("s", $moveId);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function undo(): void {
        $lastMove = $this->getLastMove();
        $gameId = $this->getGameId();

        if ($lastMove === null) {
            $stmt = $this->database->prepare('DELETE FROM moves WHERE game_id = ?');

            $stmt->bind_param("s", $gameId);
            $stmt->execute();
            return;
        }

        $prevMove = $this->getMove($lastMove)->fetch_array()[5];

        if ($prevMove == null) {
            $stmt = $this->database->prepare('DELETE FROM moves WHERE game_id = ?');

            $stmt->bind_param("s", $gameId);
            $stmt->execute();

            $this->restartGame();
            return;
        }

        $stmt = $this->database->prepare('DELETE FROM moves WHERE id = ?');

        $stmt->bind_param("s", $lastMove);
        $stmt->execute();

        $this->setLastMove($prevMove);

        $this->setState($this->getMove($prevMove)->fetch_array()[6]);

        $this->playerController->switchPlayer();
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

    public function setDefaultDeck(): void {
        $_SESSION['hand'] = [0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3], 1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]];
    }

    public function restartGame(): void {
        $_SESSION['board'] = [];
        $this->setDefaultDeck();
        $_SESSION['player'] = 0;

        unset($_SESSION['error']);
        unset($_SESSION['last_move']);

        $this->database->prepare('INSERT INTO games VALUES ()')->execute();

        $this->setGameId($this->getInsertID());
    }
}