<?php

use PHPUnit\Framework\TestCase;
use Controllers\GameController as GameController;
use Controllers\ErrorController as ErrorController;
use Database\Database as Database;
use Controllers\PlayerController as PlayerController;
class BugTests extends TestCase {

    private GameController $gameController;
    private playerController $playerController;
    private ErrorController $errorController;
    private Database $database;

    protected function setUp(): void {
        $this->database = new Database();
        $this->playerController = new PlayerController();
        $this->gameController = new GameController($this->database, $this->playerController);
        $this->errorController = new ErrorController();

        $this->database->restartGame();
    }

//    Check if piece is out of deck after playing
    public function test_PieceIsOutOfDeck_AfterBeingPlayed_BugOne() {
        $player = $this->playerController->getPlayer();
        $deck = $this->playerController->getDeck()[$player];

        $this->gameController->playPiece("Q", "0,0");
        $this->gameController->playPiece("Q", "1,0");

//       Check if Queen is out of deck
        $this->assertNotContains("Q", $deck);
    }

    public function test_MoveFromPosition_DoesNotContainOpponentPositions_BugOne() {
        $this->gameController->playPiece("Q", "0,0");
        $this->gameController->playPiece("Q", "1,0");
        $this->gameController->playPiece("B", "0,-1");

        $this->assertNotContains("0,-1", $this->gameController->currentPlayerPlayerPositions());
    }

    public function test_MoveFrom_ContainsWhiteFirstPosition() {
        $this->gameController->playPiece("Q", "0,0");
        $this->gameController->playPiece("Q", "1,0");

        $this->assertContains("0,0", $this->gameController->currentPlayerPlayerPositions());
    }

    public function test_WhiteQueen_CanMoveToPosition_BugTwo() {
        $this->gameController->playPiece("Q", "0,0");
        $this->gameController->playPiece("Q", "1,0");
        $this->gameController->movePiece("0,0", "0,1");

        $this->assertArrayHasKey("0,1", $this->gameController->getBoard());
    }

    public function test_WhiteQueen_IsNotPlacedWithinFourMoves() {
        $this->gameController->playPiece("B", "0,0");
        $this->gameController->playPiece("B", "0,1");
        $this->gameController->playPiece("B", "0,-1");
        $this->gameController->playPiece("B", "0,2");
        $this->gameController->playPiece("S", "0,-2");
        $this->gameController->playPiece("A", "0,3");

        $this->gameController->playPiece("S", "0,-3");

        $this->assertArrayNotHasKey("0,-3", $this->gameController->getBoard());
        $this->assertEquals("Must play queen bee", $this->errorController->getError());
    }

    public function test_BlackQueen_IsNotPlacedWithinFourMoves() {
        $this->gameController->playPiece("Q", "0,0");
        $this->gameController->playPiece("B", "0,1");
        $this->gameController->playPiece("B", "0,-1");
        $this->gameController->playPiece("B", "0,2");
        $this->gameController->playPiece("S", "0,-2");
        $this->gameController->playPiece("A", "0,3");
        $this->gameController->playPiece("S", "0,-3");

        $this->gameController->playPiece("A", "0,4");

        $this->assertArrayNotHasKey("0,4", $this->gameController->getBoard());
        $this->assertEquals("Must play queen bee", $this->errorController->getError());
    }

    public function test_PlayPiece_OnPosPreviouslyMovedPiece() {
        $this->gameController->playPiece("Q", "0,0");
        $this->gameController->playPiece("B", "0,1");
        $this->gameController->playPiece("B", "0,-1");
        $this->gameController->playPiece("B", "0,2");
        $this->gameController->playPiece("S", "1,-1");
        $this->gameController->playPiece("A", "0,3");
        $this->gameController->playPiece("S", "1,-2");
        $this->gameController->playPiece("S", "-1,-4");
        $this->gameController->playPiece("S", "0,-2");
        $this->gameController->playPiece("S", "0,4");
        $this->gameController->movePiece("1,-2", "1,-3");
        $this->gameController->playPiece("A", "-1,2");
        $this->gameController->playPiece("A", "1,-2");

        $this->assertArrayHasKey("1,-2", $this->gameController->getBoard());
    }
}