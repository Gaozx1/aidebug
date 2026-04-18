<?php
require_once 'config.php';
configureSession();
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['language'])) {
    $lang = $_POST['language'];
    setLanguage($lang);
    
    if (isset($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('Location: index.php');
    }
    exit;
}

if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    setLanguage($lang);
    
    if (isset($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('Location: index.php');
    }
    exit;
}

header('Location: index.php');
exit;