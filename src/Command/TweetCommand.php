<?php

namespace App\Command;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Service\Stats;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:tweet',
    description: 'Generate and tweet stats',
)]
class TweetCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface                                            $httpClient,
        private readonly Stats                                                          $stats,
        private readonly CacheInterface                                                 $cache,
        #[Autowire('%kernel.environment%')] private readonly string                     $env,
        #[Autowire('%env(string:LOL_API_KEY)%')] private readonly string                $lolApiKey,
        #[Autowire('%env(string:LOL_LEAGUE_ID)%')] private readonly string              $lolLeagueId,
        #[Autowire('%env(string:TWITTER_CONSUMER_KEY)%')] private readonly string       $twitterConsumerKey,
        #[Autowire('%env(string:TWITTER_CONSUMER_SECRET)%')] private readonly string    $twitterConsumerSecret,
        #[Autowire('%env(string:TWITTER_OAUTH_TOKEN)%')] private readonly string        $twitterOAuthToken,
        #[Autowire('%env(string:TWITTER_OAUTH_TOKEN_SECRET)%')] private readonly string $twitterOAuthTokenSecret,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $response = $this->httpClient->request('GET', 'https://esports-api.lolesports.com/persisted/gw/getSchedule?hl=fr-FR&leagueId=' . $this->lolLeagueId, [
            'headers' => [
                'X-Api-Key' => $this->lolApiKey
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            $io->error('Unable to retreive data');
            return Command::FAILURE;
        }

        $events = $response->toArray()['data']['schedule']['events'];

        $teamsCode = [];
        $teams = [];
        foreach ($events as $match) {
            if (!isset($match['match'])) {
                continue;
            }
            foreach ($match['match']['teams'] as $team) {
                $teams[$team['name']] = $team['record']['wins'];
                $teamsCode[$team['name']] = $team['code'];
            }
        }

        $matchesData = array_filter($events, function (array $element) {
            return $element['state'] !== 'completed' && isset($element['match']);
        });

        if (empty($matchesData)) {
            $io->success('No matches');
            return parent::SUCCESS;
        }

        $cache = $this->cache->getItem('matches-remaining');
        if ($cache->isHit() && (int)$cache->get() <= \count($matchesData)) {
            $io->success('No update');
            return parent::SUCCESS;
        }

        $cache->set(\count($matchesData));
        $cache->expiresAt(null);
        $this->cache->save($cache);

        $matches = [];
        foreach ($matchesData as $match) {
            $matches[] = [
                $match['match']['teams'][0]['name'] => $match['match']['teams'][1]['name']
            ];
        }

        $data = $this->stats->process($teams, $matches);
        $text = sprintf("⚔️ %s matches left\n⚡ %s results calculated\n\nQualification:\n", \count($matches), $data['possibilities']);

        $i = 1;
        foreach ($data['stats'] as $team => $ties) {
            $text .= ($i > 8 ? '🚨' : ($ties['notie']['percent'] === 100.0 ? '✅' : '❓')) . '#' . $teamsCode[$team] . 'WIN : ' . $ties['notie']['percent'] . "%\n";
            $i++;
        }

        $text .= "\n#" . $events[0]['league']['name'];

        if ($this->env !== 'dev') {
            $twitter = new TwitterOAuth($this->twitterConsumerKey, $this->twitterConsumerSecret, $this->twitterOAuthToken, $this->twitterOAuthTokenSecret);
            $twitter->setApiVersion(2);
            $twitter->post('tweets', ['text' => $text]);
            $io->success('Tweet sent');
        }

        return Command::SUCCESS;
    }
}
