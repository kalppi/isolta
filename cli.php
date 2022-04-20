<?php

use MegaCorp\Api\Client\Prh;
use MegaCorp\Cache\BadCache;
use MegaCorp\Exception\ApiErrorException;
use MegaCorp\Exception\ValidationException;

require __DIR__ . '/vendor/autoload.php';

if (count($argv) < 2) {
    die("Käyttö: cli.php tähänid\n");
}

list(, $id) = $argv;

$cache = new BadCache;

$client = new Prh($cache);

try {
    $data = $client->fetchInfo($id);

    print_r($data);
} catch (ValidationException $e) {
    print "Voe tokkiinsa, kahoppa mitä syöttelet\n";
} catch (ApiErrorException $e) {
    print "Jokii mäni mönkää. Oisko y-tunnus väärin?\n";
}
