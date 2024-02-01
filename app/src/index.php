<?php

session_start();

use Controllers\ErrorController as ErrorController;
use Controllers\GameController as GameController;
use Controllers\PlayerController as PlayerController;
use Database\Database as Database;

require_once './vendor/autoload.php';

$database = new Database();
$errorController = new ErrorController();
$playerController = new PlayerController();
$gameController = new GameController($database, $playerController);

if (array_key_exists('restart', $_POST) || $gameController->getBoard() == null) {
    unset($_SESSION['error']);
    $database->restartGame();
}

$board = $gameController->getBoard();
$player = $playerController->getPlayer();

// Handle 'Pass' button press
if(array_key_exists('pass', $_POST)) {
    unset($_SESSION['error']);
    $gameController->pass();
    header("Location: ./index.php");

}

// Handle 'Restart' button press
if(array_key_exists('restart', $_POST)) {
    unset($_SESSION['error']);
    $database->restartGame();
    header("Location: ./index.php");
}

// Handle 'Undo' button press
if(array_key_exists('undo', $_POST)) {
    unset($_SESSION['error']);
    $database->undo();
    header("Location: ./index.php");
}

// Handle 'Play' button press
if(array_key_exists('play', $_POST)) {
    $piece = $_POST['piece'];
    $to = $_POST['to'];

    unset($_SESSION['error']);
    $gameController->playPiece($piece, $to);
    header("Location: ./index.php");
}

if(array_key_exists('move', $_POST) && isset($_POST['from'])) {
    $from = $_POST['from'];
    $to = $_POST['to'];

    unset($_SESSION['error']);
    $gameController->movePiece($from, $to);
    header("Location: ./index.php");
}

$deck = $playerController->getDeck();
$to = $gameController->getToPositions();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hive</title>
    <link rel="stylesheet" href="./css/board.css">
    <link rel="stylesheet" href="./css/tile.css">
    <link rel="stylesheet" href="./css/player.css">
    <link rel="stylesheet" href="./css/util.css">
</head>
<body>
<div class="board">
    <?php
    $min_p = 1000;
    $min_q = 1000;
    foreach ($board as $pos => $tile) {
        $pq = explode(',', $pos);
        if ($pq[0] < $min_p) $min_p = $pq[0];
        if ($pq[1] < $min_q) $min_q = $pq[1];
    }
    foreach (array_filter($board) as $pos => $tile) {
        $pq = explode(',', $pos);
        $h = count($tile);
        echo '<div class="tile player';
        echo $tile[$h-1][0];
        if ($h > 1) {
            echo ' stacked';
        }
        echo '" style="left: ';
        echo ($pq[0] - $min_p) * 4 + ($pq[1] - $min_q) * 2;
        echo 'em; top: ';
        echo ($pq[1] - $min_q) * 4;
        echo "em;\">($pq[0],$pq[1])<span>";
        echo $tile[$h-1][1];
        echo '</span></div>';
    }
    ?>
</div>
<div class="hand">
    White:
    <?php
    foreach ($deck[0] as $tile => $ct) {
        for ($i = 0; $i < $ct; $i++) {
            echo '<div class="tile player0"><span>'.$tile."</span></div> ";
        }
    }
    ?>
</div>
<div class="hand">
    Black:
    <?php
    foreach ($deck[1] as $tile => $ct) {
        for ($i = 0; $i < $ct; $i++) {
            echo '<div class="tile player1"><span>'.$tile."</span></div> ";
        }
    }
    ?>
</div>
<div class="turn">
    Turn: <?php if ($player == 0) echo "White"; else echo "Black"; ?>
</div>
<form method="post">
    <label>
        <select name="piece">
            <?php
            foreach ($deck[$player] as $tile => $ct) {
                if ($ct !== 0) {
                    echo "<option value=\"$tile\">$tile</option>";
                }
            }
            ?>
        </select>
    </label>
    <label>
        <select name="to">
            <?php
            foreach ($to as $pos) {
                echo "<option value=\"$pos\">$pos</option>";
            }
            ?>
        </select>
    </label>
    <input type="submit" name="play" value="Play">
</form>
<form method="post">
    <label>
        <select name="from">
            <?php
            foreach (array_keys($board) as $pos) {
                if ($gameController->playerOwnsTile($board, $player, $pos)) {
                    echo "<option value=\"$pos\">$pos</option>";
                }
            }
            ?>
        </select>
    </label>
    <label>
        <select name="to">
            <?php
            foreach ($to as $pos) {
                echo "<option value=\"$pos\">$pos</option>";
            }
            ?>
        </select>
    </label>
    <input type="submit" name="move" value="Move">
</form>
<form method="post">
    <input type="submit" name="pass" value="Pass">
</form>
<form method="post">
    <input type="submit" name="restart" value="Restart">
</form>
<strong>
    <?php $errorController->printError(); ?>
</strong>
<ol>
    <?php
    $database->printMoves();
    ?>
</ol>
<form method="post">
    <input type="submit" name="undo" value="Undo">
</form>
</body>
</html>

