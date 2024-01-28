<?php

namespace Controllers;

class ErrorController {

    public function getError() {
        return $_SESSION['error'] ?? null;
    }

    public function setError($error): void {
        $_SESSION['error'] = $error;
    }

    public function unsetError(): void {
        unset($_SESSION['ERROR']);
    }
}