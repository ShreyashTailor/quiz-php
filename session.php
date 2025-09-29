<?php
session_start();
$_SESSION['username'] = 'shreyash';
$_SESSION['password'] = '1234';
echo 'Session variables are set.';

if (isset($_SESSION['username']) && isset($_SESSION['password'])) {
    if ($_SESSION['username'] == 'shreyash' && $_SESSION['password'] == '1234') {
        echo '<br>Login successful! Welcome, ' . $_SESSION['username'];
    } else {
        echo '<br>Login failed! Invalid username or password.';
    }
} else {
    echo '<br>No session variables set.';
}