<?php

session_start();

// Подключаем lightopenid
require 'openid.php';

try {
    // Создаем объект LightOpenID.  Замените 'http://yourdomain.com/' на свой домен!
    $openid = new LightOpenID('http://dota2statistics.ru/');

    // Если пользователь не аутентифицирован
    if(!$openid->mode) {
        // Если нажата кнопка "Войти через Steam"
        if(isset($_GET['login'])) {
            // Задаем URL, на который Steam перенаправит пользователя после аутентификации
            $openid->identity = 'http://steamcommunity.com/openid';
            header('Location: ' . $openid->authUrl());
        }

        // Отображаем кнопку "Войти через Steam"
        echo '<form action="?login" method="post">';
        echo '<button type="submit">Войти через Steam</button>';
        echo '</form>';

        // Если есть сообщение об ошибке
        if (isset($_SESSION['steam_error'])) {
            echo '<p style="color: red;">' . $_SESSION['steam_error'] . '</p>';
            unset($_SESSION['steam_error']);
        }
    } elseif ($openid->mode == 'cancel') {
        echo "Authentication cancelled";
    } else {
        // Пользователь аутентифицирован

        // Пытаемся получить SteamID
        if ($openid->validate()) {
            $id = $openid->identity;
            // SteamID имеет формат: http://steamcommunity.com/openid/id/76561197960435530
            $ptn = "/^http:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
            preg_match($ptn, $id, $matches);

            // Получаем ыSteamID64
            $steamID64 = $matches[1];

            // Сохраняем SteamID в сессии
            $_SESSION['steamid'] = $steamID64;

            echo "Logged in as SteamID: " . $steamID64 . "<br>";
            echo '<a href="logout.php">Выйти</a>';
        } else {
            $_SESSION['steam_error'] = "Ошибка при получении SteamID.";
            header('Location: index.php');
            exit;
        }
    }

} catch(ErrorException $e) {
    echo $e->getMessage();
}
?>