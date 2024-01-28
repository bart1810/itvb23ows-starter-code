<?php

namespace controllers;

class PlayerController {

    public function getPlayer() {
        return $_SESSION['player'];
    }

    public function switchPlayer(): void {
        $_SESSION['player'] = 1 - $_SESSION['player'];
    }

    public function getDeck() {
        return $_SESSION['hand'];
    }
}