<?php

namespace App\Service;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class ImdbService
{
    const GENRES = [
        'action', 'comedy', 'family',
        'history', 'mystery', 'sci_fi',
        'war', 'adventure', 'crime',
        'fantasy', 'horror', 'western',
        'romance', 'drama',
    ];

    public static function isValidGenre(?string $genre = null)
    {
        if (is_null($genre) || in_array($genre, self::GENRES)) {
            return true;
        }

        return false;
    }

    public static function recommendMovies(?string $genre = null) : array
    {
        $result = self::search($genre);
        
        return self::scrape($result);
    }

    private static function scrape(Crawler $result) : array
    {
        return $result->filter('.lister-list .lister-item-content')
            ->slice(length: 10)
            ->each(function ($node) {
                preg_match(
                    '/(?<=Votes: )(?(?=.* \|).*(?= \|)|.*)/',
                    $node->filter('.sort-num_votes-visible')->text(),
                    $matches
                );

                return [
                    $node->filter('.lister-item-header a')->text(),
                    $node->filter('.lister-item-header .lister-item-year')->text(),
                    $node->filter('.genre')->text(),
                    $node->filter('.ratings-imdb-rating')->attr('data-value'),
                    $matches[0],
                ];
            });
    }

    private static function search(?string $genre = null) : Crawler
    {
        $client = new Client(
            HttpClient::create([
                'headers' => ['Accept-Language' => 'en-US,en;q=0.5']
            ])
        );

        $crawler = $client->request('GET', 'https://www.imdb.com/search/title/');

        $form = $crawler->selectButton('Search')->form();

        $formData = [
            'title_type' => 'feature',
            'groups' => 'top_1000',
        ];

        if (isset($genre)) {
            $formData['genres'] = $genre;
        }

        $form->disableValidation()->setValues($formData);

        return $client->submit($form);
    }
}
