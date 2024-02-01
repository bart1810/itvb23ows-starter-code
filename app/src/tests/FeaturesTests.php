<?php

use PHPUnit\Framework\TestCase;
use Controllers\GameController as GameController;
use Controllers\ErrorController as ErrorController;
use Database\Database as Database;
use Controllers\PlayerController as PlayerController;
class FeaturesTests extends TestCase {
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

    public function test_ReturnFalse_WhenGrasshopperJumpsToSamePosition() {
        $result = $this->gameController->slideForGrasshopper("0,0", "0,0");

        $this->assertFalse($result);
    }

    public function test_MoveGrasshopper_JumpDiagonal_ToOpenPos() {
        $this->gameController->playPiece("Q", "0,0");
        $this->gameController->playPiece("Q", "0,1");
        $this->gameController->playPiece("G", "0,-1");
        $this->gameController->playPiece("B", "0,2");
        $this->gameController->movePiece("0,-1", "0,3");

        $this->assertArrayHasKey("0,3", $this->gameController->getBoard());
    }

    public function test_MoveGrasshopper_Left_ToOpenPos() {
        $this->gameController->playPiece("Q", "0,0");
        $this->gameController->playPiece("Q", "-1,0");
        $this->gameController->playPiece("G", "1,0");
        $this->gameController->playPiece("B", "-2,0");
        $this->gameController->movePiece("1,0", "-3,0");

        $this->assertArrayHasKey("-3,0", $this->gameController->getBoard());
    }

    public function test_MoveGrasshopper_Right_ToOpenPos() {
        $this->gameController->playPiece("Q", "0,0");
        $this->gameController->playPiece("Q", "1,0");
        $this->gameController->playPiece("G", "-1,0");
        $this->gameController->playPiece("B", "2,0");
        $this->gameController->movePiece("-1,0", "3,0");

        $this->assertArrayHasKey("3,0", $this->gameController->getBoard());
    }

    public function test_MoveGrasshopper_CanNotJumpOverEmptyTile() {
        $this->gameController->playPiece("Q", "0,0");

    }
}