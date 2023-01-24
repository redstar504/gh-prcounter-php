<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

// GH Access token is required here as it provides an increased rate limit
define('GH_ACCESS_TOKEN', '', false);
define('GH_REPOSITORY', 'Expensify/App', false);
define('FILTER_FROM', strtotime('2022-01-01T00:00:01Z'), false);
define('FILTER_TO', strtotime('2022-12-31T23:59:59Z'), false);

if ('' === GH_ACCESS_TOKEN) {
    echo "Must provide your GH access token to run the script.\n";
    echo "https://github.com/settings/tokens\n";
    exit;
}

$client = new Client([
    'base_uri' => 'https://api.github.com/',
    'headers' => [
        'Accept' => 'application/vnd.github+json',
        'Authorization' => sprintf('Bearer %s', GH_ACCESS_TOKEN)
    ]
]);

$data = [];
$searching = true;
$finding = false;
$page = 1;

while ($searching) {
    echo "Fetching page: $page\n";

    $response = $client->request('GET',
        sprintf('repos/%s/pulls', GH_REPOSITORY), [
        'query' => [
            'per_page' => 100,
            'page' => $page,
            'sort' => 'created',
            'direction' => 'desc',
            'state' => 'all',
        ]
    ]);

    $results = json_decode($response->getBody(), true);

    foreach ($results as $pr) {
        $createdAt = strtotime($pr['created_at']);

        if ($createdAt >= FILTER_FROM && $createdAt <= FILTER_TO) {
            $finding = true;
            $username = $pr['user']['login'];
            $data[$username] = !isset($data[$username]) ? 1 : ++$data[$username];
        } elseif ($finding === true) {
            echo "Ending search\n";
            $searching = $finding = false;
            break;
        }
    }

    $page++;
}

if (!count($data)) {
    exit;
}

// Sort users by number of PRs
arsort($data);

echo "----------------------------------\n";
echo "Results below:\n";
echo "----------------------------------\n";

foreach ($data as $author => $count) {
    printf('%s: %d%s', $author, $count, "\n");
}

echo "----------------------------------\n";
