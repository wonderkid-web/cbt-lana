<?php
// config/auth.php
session_start();

function checkLogin() {
    if (!isset($_SESSION['id_user'])) {
        header("Location: ../login.php");
        exit;
    }
}
?>