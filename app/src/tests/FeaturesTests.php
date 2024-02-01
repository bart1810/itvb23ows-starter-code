<?php

use PHPUnit\Framework\TestCase;
use Controllers\GameController as GameController;
use Database\Database as Database;
use Controllers\PlayerController as PlayerController;
class FeaturesTests extends TestCase {
    private GameController $gameController;

    protected function setUp(): void {
        $database = new Database();
        $playerController = new PlayerController();
        $this->gameController = new GameController($database, $playerController);

        $database->restartGame();
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
        $this->gameController->playPiece("Q", "-1,0");
        $this->gameController->playPiece("G", "0,1");
        $this->gameController->playPiece("B", "-2,0");
        $this->gameController->movePiece("0,1", "1,-1");

        $this->assertArrayNotHasKey("1,-1", $this->gameController->getBoard());
    }

    public function test_MoveAnt_OneTile() {
        $this->gameController->playPiece('Q', '0,0');
        $this->gameController->playPiece('Q', '1,0');
        $this->gameController->playPiece('A', '-1,0');
        $this->gameController->playPiece('B', '2,0');
        $this->gameController->movePiece('-1,0', '0,-1');

        $this->assertArrayHasKey('0,-1', $this->gameController->getBoard());
    }

    public function test_MoveAnt_MultipleTiles() {
        $this->gameController->playPiece('Q', '0,0');
        $this->gameController->playPiece('Q', '1,0');
        $this->gameController->playPiece('A', '-1,0');
        $this->gameController->playPiece('B', '2,0');
        $this->gameController->movePiece('-1,0', '2,-1');

        $this->assertArrayHasKey('2,-1', $this->gameController->getBoard());
    }

    public function test_MoveAnt_AntDoesNotSlide() {
        $this->gameController->playPiece('Q', '0,0');
        $this->gameController->playPiece('Q', '1,0');
        $this->gameController->playPiece('B', '-1,1');
        $this->gameController->playPiece('B', '1,1');
        $this->gameController->playPiece('A', '-2,1');
        $this->gameController->playPiece('B', '0,2');
        $this->gameController->movePiece('-2,1', '0,1');

        $this->assertArrayNotHasKey("0,1", $this->gameController->getBoard());
    }
}