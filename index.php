<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php
session_start();

$steam_api_key = '99094D4D82A927A4044EC594692259E4';
$steam_id = htmlspecialchars($_SESSION['steamid'], ENT_QUOTES, 'UTF-8');
$steamID32 = convertSteamID64ToSteamID32($steam_id);
$url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=$steam_api_key&steamids=$steam_id";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($ch);
curl_close($ch);
$data = json_decode($result, true);



if (isset($data['response']['players'][0])) {
    $player = $data['response']['players'][0];
    $steam_id = $player['steamid'];
    $persona_name = $player['personaname'];
    $avatar_url = $player['avatarfull'];
    $profile_url = $player['profileurl'];
};
$json_data = file_get_contents($url);
$data = json_decode($json_data, true);
if ($data === NULL) {
    die("Ошибка при декодировании JSON.");
}
if (isset($data['response']['players'][0])) {
    $player = $data['response']['players'][0];
  $personastate = $player['personastate']; // Статус в сети (0 - Offline, 1 - Online, 2 - Busy, 3 - Away, 4 - Snooze, 5 - looking to trade, 6 - looking to play)
};
function convertSteamID64ToSteamID32($steamID64) {
  $steamID32 = bcsub($steamID64, '76561197960265728');
  return $steamID32;
};
// Пример использования:
$steamID64 = $steam_id;  // Замените на нужный SteamID64
$steamID32 = convertSteamID64ToSteamID32($steamID64);
$account_id = $steamID32;
$url = "https://api.opendota.com/api/players/" . $account_id . "/matches";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$json_data = curl_exec($ch);
if (curl_errno($ch)) {
  die('cURL error: ' . curl_error($ch));
}
curl_close($ch);
$matches = json_decode($json_data, true);
if ($matches === NULL) {
  die("Ошибка при декодировании JSON.");
}
$wins = 0;
$losses = 0;

$button_text = 'Войти'; // Значение по умолчанию

// Здесь логика для определения, авторизован ли пользователь
// Например, проверка сессии
if (isset($_SESSION['account_id'])) {
  $button_text = 'Выйти';
}

foreach ($matches as $match) {
  $isRadiant = $match['player_slot'] < 128;  // 0-127 Radiant, 128-255 Dire
  $radiant_win = $match['radiant_win'];
  $won = ($isRadiant && $radiant_win) || (!$isRadiant && !$radiant_win);
  if ($won) {
      $wins++;
  } else {
      $losses++;
  }
}

$winrates = ($wins + $losses > 0) ? round(($wins / ($wins + $losses)) * 100, 2) : 0;
// 1. SteamID32 пользователя (OpenDota Account ID)
$account_id = $steamID32; // Замените на настоящий account_id

// 2. URL для получения истории матчей
$url = "https://api.opendota.com/api/players/" . $account_id . "/matches";

// 3. Получаем данные из API (используем cURL для надежности)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$json_data = curl_exec($ch);

if (curl_errno($ch)) {
    die('cURL error: ' . curl_error($ch));
}
curl_close($ch);

// 4. Декодируем JSON
$matches = json_decode($json_data, true);

if ($matches === NULL) {
    die("Ошибка при декодировании JSON.");
}

// 5. Подсчет количества игр и побед для каждого героя
$hero_stats = [];  // Изменяем массив, чтобы хранить статистику (кол-во игр и кол-во побед)

foreach ($matches as $match) {
    if (isset($match['hero_id'])) {
        $hero_id = $match['hero_id'];

        // Определяем, выиграл ли игрок в этом матче
        $isRadiant = $match['player_slot'] < 128;
        $radiant_win = $match['radiant_win'];
        $won = ($isRadiant && $radiant_win) || (!$isRadiant && !$radiant_win);

        // Обновляем статистику для этого героя
        if (isset($hero_stats[$hero_id])) {
            $hero_stats[$hero_id]['games']++;
            if ($won) {
                $hero_stats[$hero_id]['wins']++;
            }
        } else {
            $hero_stats[$hero_id] = [
                'games' => 1,
                'wins' => $won ? 1 : 0
            ];
        }
    }
}

//Тута войти
if (isset($hero_stats[$hero_id])){
  $okis = "Выйти";
}
else {
  $okis = "Войти";
}
//Тута войти

// 6. Сортировка героев по количеству игр (в порядке убывания)
uasort($hero_stats, function($a, $b) { // Используем uasort для сортировки массива со сложной структурой
    return $b['games'] <=> $a['games'];  // Сортируем по количеству игр (games) в порядке убывания
});

$top_hero_strings = []; // Массив для хранения строк о героях

$count = 0;
$hero_names = [];
$hero_games = [];
$hero_winrates = [];

$count = 0;
foreach ($hero_stats as $hero_id => $stats) {
    // Получаем имя героя
    $hero_name = getHeroName($hero_id);

    if ($hero_name !== null) {
        // Вычисляем винрейт
        $winrate = ($stats['games'] > 0) ? round(($stats['wins'] / $stats['games']) * 100, 2) : 0;

        // Добавляем данные в массивы
        $hero_names[] = $hero_name;
        $hero_games[] = $stats['games'];
        $hero_winrates[] = $winrate;
    }

    $count++;
    if ($count >= 5) {
        break;
    }
}

//  Присваиваем значения отдельным переменным (менее гибко, не рекомендуется)
$hero1_name = isset($hero_names[0]) ? $hero_names[0] : "";
$hero1_games = isset($hero_games[0]) ? $hero_games[0] : "";
$hero1_winrate = isset($hero_winrates[0]) ? $hero_winrates[0] : "";

$hero2_name = isset($hero_names[1]) ? $hero_names[1] : "";
$hero2_games = isset($hero_games[1]) ? $hero_games[1] : "";
$hero2_winrate = isset($hero_winrates[1]) ? $hero_winrates[1] : "";

$hero3_name = isset($hero_names[2]) ? $hero_names[2] : "";
$hero3_games = isset($hero_games[2]) ? $hero_games[2] : "";
$hero3_winrate = isset($hero_winrates[2]) ? $hero_winrates[2] : "";

$hero4_name = isset($hero_names[3]) ? $hero_names[3] : "";
$hero4_games = isset($hero_games[3]) ? $hero_games[3] : "";
$hero4_winrate = isset($hero_winrates[3]) ? $hero_winrates[3] : "";

$hero5_name = isset($hero_names[4]) ? $hero_names[4] : "";
$hero5_games = isset($hero_games[4]) ? $hero_games[4] : "";
$hero5_winrate = isset($hero_winrates[4]) ? $hero_winrates[4] : "";

function getHeroName($hero_id) {
    // Здесь нужно реализовать логику для получения имени героя по его ID.
    // (Реализация зависит от выбранного вами способа: API, файл, база данных)

    // Пример использования OpenDota API (как и в предыдущем ответе)
    $hero_data_url = "https://api.opendota.com/api/heroes";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $hero_data_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $heroes_json = curl_exec($ch);
    curl_close($ch);
    $heroes = json_decode($heroes_json, true);

    if ($heroes === null) {
        return "Неизвестный герой (ошибка API)";
    }

    foreach ($heroes as $hero) {
        if ($hero['id'] == $hero_id) {
            return $hero['localized_name'];
        }
    }
    return "Неизвестный герой (ID: " . $hero_id . ")";
}
?>
<form action="steamauth\vendor\login.php" method="POST">
  <div class="kjgkj">
    <div class="kjgkj1">
      <div class="logotip"></div>
      <div class="logotip2"></div>
      <div class="logotip3">Test.ru</div>
      <!-- <div class="logout"></div> -->
      
      <input class="logout2" type="submit" name="Ok" value="<?php echo $okis; ?>">
      <div class="light">
        <div class="light2"><img src="images/Vectortssrtsrrtsrs.svg" alt=""></div>
      </div>
      <div class="poisk">
        <div class="poisk2"><img src="images/noun-search-7429298 1.svg" alt=""></div>
      </div>
    </div>
    <div class="z1">
      
    </div>
    <div class="zet1"></div>
    <div class="zet"></div>
    <div class="kn">
      <div class="kn1"><img src="images/Vector1.svg" alt=""></div>
      <div class="kn2 text1">HOME</div>
    </div>
    <div class="zn">
      <div class="zn1"><img src="images/players.svg" alt=""></div>
      <div class="zn2 text1">800</div>
    </div>
    <div class="pn">
      <div class="pn1"><img src="images/META.svg" alt=""></div>
      <div class="pn2 text1">META</div>
    </div>
    <a href="lanes.php"><div class="mn">
      <div class="mn1"><img src="images/LANES.svg" alt=""></div>
      <div class="mn2 text1">LANES</div>
    </div></a>
    <div class="nn">
      <div class="nn1"><img src="images/players.svg" alt=""></div>
      <div class="nn2 text1">PLAYERS</div>
    </div>
    <div class="big"></div>
    <div class="big1"></div>
    <div class="zetus"><img src="images/Group 30.svg" alt=""></div>
    <div class="parus">
      <div class="parus1">

        <div class="azz1"><?php echo "<p ><a  href=\"$profile_url\">$persona_name</a></p>";?></div>
        <div class="zxc1"><?php 
    switch ($personastate) {
        case 0: echo "Не в сети"; break;
        case 1: echo "В сети"; break;
        case 2: echo "Занят"; break;
        case 3: echo "Отошел"; break;
        case 4: echo "Спит"; break;
    }
    echo "<br>"; ?></div>
        <div class="zxc2"> <?php   echo "<img src=\"$avatar_url\" alt=\"$persona_name\">";?></div>
      </div>
      <div class="parus2">
        <div class="zxc3">All Matches</div>
        <div class="zxc4"><?php echo "" . $wins . "<br>"; ?></div>
        <div class="zxc5"> - </div>
        <div class="zxc6"><?php echo "" . $losses . "<br>"; ?></div>
      </div>   
      <div class="parus3">
        <div class="zxc7">All Winrate</div>
        <div class="zxc8"><?php echo "" . $winrates . "%<br>"; ?></div>
      </div>
    </div>
    <div class="zc">
      <div class="scrolling-text text1">
        Animations!
      </div>
    </div>
    <section class="er">
      <div class="er1">
        <div class="er2 text1">Hero</div>
        <div class="er2 text1">Winrate</div>
        <div class="er2 text1">Matches</div>
        <div class="er2 text1">Pick Rate</div>
      </div>
      <div class="zik">
        <div class="zix2 text1"><?php echo htmlspecialchars($hero1_name) ?></div>
        <div class="wr text1"><?php echo htmlspecialchars($hero1_winrate) . "%" ?></div>
        <div class="mtch text1"><?php echo "" . " " . htmlspecialchars($hero1_games) . "" . "" . "" ?></div>
        <div class="pr text1">50%</div>
      </div>
      <div class="zik1">
      <div class="zix2 text1"><?php echo htmlspecialchars($hero2_name) ?></div>
        <div class="wr text1"><?php echo htmlspecialchars($hero2_winrate) . "%" ?></div>
        <div class="mtch text1"><?php echo "" . " " . htmlspecialchars($hero2_games) . "" . "" . "" ?></div>
        <div class="pr text1">50%</div>
      </div>
      <div class="zik2">
      <div class="zix2 text1"><?php echo htmlspecialchars($hero3_name) ?></div>
        <div class="wr text1"><?php echo htmlspecialchars($hero3_winrate) . "%" ?></div>
        <div class="mtch text1"><?php echo "" . " " . htmlspecialchars($hero3_games) . "" . "" . "" ?></div>
        <div class="pr text1">50%</div>
      </div>
      <div class="zik3">
      <div class="zix2 text1"><?php echo htmlspecialchars($hero4_name) ?></div>
        <div class="wr text1"><?php echo htmlspecialchars($hero4_winrate) . "%" ?></div>
        <div class="mtch text1"><?php echo "" . " " . htmlspecialchars($hero4_games) . "" . "" . "" ?></div>
        <div class="pr text1">50%</div>
      </div>
      <div class="zik4">
      <div class="zix2 text1"><?php echo htmlspecialchars($hero5_name) ?></div>
        <div class="wr text1"><?php echo htmlspecialchars($hero5_winrate) . "%" ?></div>
        <div class="mtch text1"><?php echo "" . " " . htmlspecialchars($hero5_games) . "" . "" . "" ?></div>
        <div class="pr text1">50%</div>
      </div>
    </section>
    <div class="putch">7.38 </div>
    <div class="footer">
      <img class="footer1" src="images/uss.svg" alt="">
      <div class="footer2 text1">TEST.RU</div>
    </div>
  </form>
</body>
</html>