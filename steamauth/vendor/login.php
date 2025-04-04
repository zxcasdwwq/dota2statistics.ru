<?php
session_start();
if (isset($_SESSION['steamid'])) { // Проверяем СЕССИЮ, а не кнопку
    // Пользователь уже залогинен
    $steamid = htmlspecialchars($_SESSION['steamid'], ENT_QUOTES, 'UTF-8'); // Экранируем
    echo "Logged in as SteamID: " . $steamid . "<br>";
    echo '<a href="logout.php">Выйти</a>';
} else {
    // Пользователь не залогинен
    echo '<a href="/steamauth/steam_auth.php">Войти через Steam</a>';
}
?>