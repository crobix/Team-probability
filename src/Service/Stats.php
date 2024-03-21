<?php

namespace App\Service;

readonly class Stats
{

    /**
     * @param array<string, int> $teams
     * @param array<array<string, string> $matchs
     */
    public function process(array $teams, array $matchs): array
    {

// PARAMETRES
        $qualified = 8;
// PARAMETRES


//Nombre de possibilités
        $total = pow(2, \count($matchs));
        $teamsQualified = [];
        $teamsQualifiedNoTie = [];
        for ($i = 0; $i < pow(2, \count($matchs)); $i++) {
            $probability = $teams;
            $matchsResult = str_split(str_pad(decbin($i), \count($matchs), '0', STR_PAD_LEFT));

            //ajoute les resultat du match au classement
            foreach ($matchsResult as $index => $match) {
                if ($match === "0") {
                    $probability[array_keys($matchs[$index])[0]]++;
                } else {
                    $probability[array_values($matchs[$index])[0]]++;
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

            $lengthNoTie = $qualified;
            for ($k = $lengthNoTie; $k >= 1; $k--) {
                $points = array_values($probability);
                if ($points[$k] !== $points[$k - 1]) {
                    break;
                }
                $lengthNoTie--;
            }

            $resultNoTie = array_slice($probability, 0, $lengthNoTie, true);
            foreach ($resultNoTie as $teamQualified => $points) {
                if (array_key_exists($teamQualified, $teamsQualifiedNoTie)) {
                    $teamsQualifiedNoTie[$teamQualified]++;
                } else {
                    $teamsQualifiedNoTie[$teamQualified] = 1;
                }
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
        }


        $stats = [];
//calcul de nombre de fois ou l'equipe a pu se qualifier
        foreach ($teamsQualified as $team => $numberQualified) {
            $stats[$team][] = array_key_exists($team, $teamsQualifiedNoTie) ? round(($teamsQualifiedNoTie[$team] / $total) * 100, 2, PHP_ROUND_HALF_DOWN) : 0;
            $stats[$team][] = round(($numberQualified / $total) * 100, 2, PHP_ROUND_HALF_DOWN);
        }
        uasort($stats, function ($a, $b) {
            return ($a > $b) ? -1 : 1;
        });

        $data = [];
        foreach ($stats as $team => $stat) {
            $data[$team] = [
                'notie' => [
                    'percent' => max($stat[0], 0),
                    'value' => $teamsQualifiedNoTie[$team],
                ],
                'tie' => [
                    'percent' => max($stat[1], 0),
                    'value' => $teamsQualified[$team]
                ],
            ];
        }

        return [
            'possibilities' => number_format($total, 0, ', ', ' '),
            'stats' => $data
        ];

    }
}
