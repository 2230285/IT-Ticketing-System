<?php
// index.php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: Views/Dashboard.php");
} else {
    header("Location: Auth/Login.php");
}
exit;