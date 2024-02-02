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

//    Check if it returns false if grasshopper wants to jump to same place
    public function test_MoveGrasshopper_ToSamePos() {
        $result = $this->gameController->slideForGrasshopper("0,0", "0,0");

        $this->assertFalse($result);
    }

//    Check if grasshopper can jump diagonal to an open pos
    public function test_MoveGrasshopper_JumpDiagonal_ToOpenPos() {
        $this->gameController->playPiece("Q", "0,0");
        $this->gameController->playPiece("Q", "0,1");
        $this->gameController->playPiece("G", "0,-1");
        $this->gameController->playPiece("B", "0,2");
        $this->gameController->movePiece("0,-1", "0,3");

        $this->assertArrayHasKey("0,3", $this->gameController->getBoard());
    }

//    Check if grasshopper can jump left to an open pos
    public function test_MoveGrasshopper_Left_ToOpenPos() {
        $this->gameController->playPiece("Q", "0,0");
        $this->gameController->playPiece("Q", "-1,0");
        $this->gameController->playPiece("G", "1,0");
        $this->gameController->playPiece("B", "-2,0");
        $this->gameController->movePiece("1,0", "-3,0");

        $this->assertArrayHasKey("-3,0", $this->gameController->getBoard());
    }

//    Check if grasshopper can jump right to an open pos
    public function test_MoveGrasshopper_Right_ToOpenPos() {
        $this->gameController->playPiece("Q", "0,0");
        $this->gameController->playPiece("Q", "1,0");
        $this->gameController->playPiece("G", "-1,0");
        $this->gameController->playPiece("B", "2,0");
        $this->gameController->movePiece("-1,0", "3,0");

        $this->assertArrayHasKey("3,0", $this->gameController->getBoard());
    }

//    Check if grasshopper can not jump over an empty tile
    public function test_MoveGrasshopper_CanNotJumpOverEmptyTile() {
        $this->gameController->playPiece("Q", "0,0");
        $this->gameController->playPiece("Q", "-1,0");
        $this->gameController->playPiece("G", "0,1");
        $this->gameController->playPiece("B", "-2,0");
        $this->gameController->movePiece("0,1", "1,-1");

        $this->assertArrayNotHasKey("1,-1", $this->gameController->getBoard());
    }

//    Check if ant can slide one tile
    public function test_MoveAnt_OneTile() {
        $this->gameController->playPiece('Q', '0,0');
        $this->gameController->playPiece('Q', '1,0');
        $this->gameController->playPiece('A', '-1,0');
        $this->gameController->playPiece('B', '2,0');
        $this->gameController->movePiece('-1,0', '0,-1');

        $this->assertArrayHasKey('0,-1', $this->gameController->getBoard());
    }

//    Check if ant can slide multiple tiles
    public function test_MoveAnt_MultipleTiles() {
        $this->gameController->playPiece('Q', '0,0');
        $this->gameController->playPiece('Q', '1,0');
        $this->gameController->playPiece('A', '-1,0');
        $this->gameController->playPiece('B', '2,0');
        $this->gameController->movePiece('-1,0', '2,-1');

        $this->assertArrayHasKey('2,-1', $this->gameController->getBoard());
    }

//    Check if ant can not slide in the middle
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

//    Check if spider can slide 3 tiles, should be possible
    public function test_MoveSpider_SlideThreeTiles() {
        $this->gameController->playPiece('Q', '0,0');
        $this->gameController->playPiece('Q', '1,0');
        $this->gameController->playPiece('S', '-1,0');
        $this->gameController->playPiece('B', '2,0');
        $this->gameController->playPiece('B', '-1,1');
        $this->gameController->playPiece('B', '3,0');
        $this->gameController->movePiece("-1,0", "2,-1");

        $this->assertArrayHasKey("2,-1", $this->gameController->getBoard());
    }

    //  Check if spider can slide 1 tile, should not be possible
    public function test_MoveSpider_SlideOneTile() {
        $this->gameController->playPiece('Q', '0,0');
        $this->gameController->playPiece('Q', '1,0');
        $this->gameController->playPiece('S', '-1,0');
        $this->gameController->playPiece('B', '2,0');
        $this->gameController->movePiece("-1,0", "0,1");

        $this->assertArrayNotHasKey("0,1", $this->gameController->getBoard());
    }

//  Check if spider can slide 2 tiles, should not be possible
    public function test_MoveSpider_SlideTwoTiles() {
        $this->gameController->playPiece('Q', '0,0');
        $this->gameController->playPiece('Q', '1,0');
        $this->gameController->playPiece('S', '-1,0');
        $this->gameController->playPiece('B', '2,0');
        $this->gameController->playPiece('B', '-1,1');
        $this->gameController->playPiece('B', '3,0');
        $this->gameController->movePiece("-1,0", "0,1");

        $this->assertArrayNotHasKey("0,1", $this->gameController->getBoard());
    }

    //  Check if spider can slide 4 tiles, should not be possible
    public function test_MoveSpider_SlideFourTiles() {
        $this->gameController->playPiece('Q', '0,0');
        $this->gameController->playPiece('Q', '-1,0');
        $this->gameController->playPiece('B', '1,0');
        $this->gameController->playPiece('B', '-2,0');
        $this->gameController->playPiece('S', '1,-1');
        $this->gameController->playPiece('B', '-3,0');
        $this->gameController->movePiece('1,-1', '-3,-1');

        $this->assertArrayNotHasKey('-3,-1', $this->gameController->getBoard());
    }
}