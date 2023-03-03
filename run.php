<?php
// PARAMETRES
$qualified = 6;
$teams = [
    "LDLC" => 10,
    "GAMEWARD" => 8,
    "BDS" => 8,
    "TEAMGO" => 8,
    "SOLARY" => 7,
    "VITALITY" => 7,
    "AEGIS" => 6,
    "KCORP" => 5,
    "BKROG" => 5,
    "IZIDREAM" => 2,
];


$matchs = [
    ["BDS" => "AEGIS"],
    ["TEAMGO" => "GAMEWARD"],
    ["SOLARY" => "KCORP"],
    ["VITALITY" => "IZIDREAM"],
    ["LDLC" => "KCORP"],
    ["GAMEWARD" => "BKROG"],
    ["SOLARY" => "VITALITY"],
    ["BDS" => "TEAMGO"],
    ["AEGIS" => "IZIDREAM"],
    ["GAMEWARD" => "LDLC"],
    ["KCORP" => "VITALITY"],
    ["BKROG" => "BDS"],
    ["SOLARY" => "AEGIS"],
    ["TEAMGO" => "IZIDREAM"],
    ["VITALITY" => "LDLC"],
    ["BDS" => "GAMEWARD"],
    ["KCORP" => "AEGIS"],
    ["IZIDREAM" => "BKROG"],
    ["SOLARY" => "TEAMGO"],
    ["LDLC" => "BDS"],
    ["AEGIS" => "VITALITY"],
    ["IZIDREAM" => "GAMEWARD"],
    ["KCORP" => "TEAMGO"],
    ["SOLARY" => "BKROG"],
];

// PARAMETRES


echo "Analyse pour les " . \count($matchs) . " matchs restants\n";
echo "Calcul des possibilités ...\n";

//Nombre de possibilités
$total = pow(2, \count($matchs));
$teamsQualified = [];
for ($i = 0; $i < pow(2, \count($matchs)); $i++) {
    $probability = $teams;
    $matchsResult = str_split(str_pad(decbin($i), \count($matchs), '0', STR_PAD_LEFT));

    //ajoute les resultat du match au classement
    foreach ($matchsResult as $index => $match) {
        if ($match === "0") {
            $probability[array_values($matchs[$index])[0]]++;
        } else {
            $probability[array_keys($matchs[$index])[0]]++;
        }
    }

    //trie le nouveau classement
    uasort($probability, function ($a, $b) {
        return ($a > $b) ? -1 : 1;
    });

    //verifie la possibilité de tiebreak
    $length = $qualified;
    for ($j = $length - 1; $j < \count($probability) - 1; $j++) {
        $points = array_values($probability);
        if ($points[$j] !== $points[$j + 1]) {
            break;
        }
        $length++;
    }

    //si qualifié ajoute l'equipe dans un tableau de qualification
    $result = array_slice($probability, 0, $length, true);
    foreach ($result as $teamQualified => $points) {
        if (array_key_exists($teamQualified, $teamsQualified)) {
            $teamsQualified[$teamQualified]++;
        } else {
            $teamsQualified[$teamQualified] = 1;
        }
    }

    echo "Progress: " . round($i / $total * 100, 2) . "%\r";
}

echo number_format($total, 0, ', ', ' ') . " trouvées\n";

echo "Génération des stats ...\n";
$stats = [];
//calcul de nombre de fois ou l'equipe a pu se qualifier
foreach ($teamsQualified as $team => $numberQualified) {
    $stats[$team] = round(($numberQualified / $total) * 100, 2, PHP_ROUND_HALF_DOWN);
}
uasort($stats, function ($a, $b) {
    return ($a > $b) ? -1 : 1;
});

echo "Chances de qualification ...\n";
foreach ($stats as $team => $stat) {
    echo $team . ' => ' . ($stat <= 0 ? "Out" : $stat . "%") . "\n";
}
