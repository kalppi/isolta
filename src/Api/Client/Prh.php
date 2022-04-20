<?php

namespace MegaCorp\Api\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use JsonException;
use MegaCorp\Cache\ICache;
use MegaCorp\Exception\ApiErrorException;
use MegaCorp\Exception\ValidationException;
use RuntimeException;

final class Prh
{
    private const FIELD_WEB = "www-adress";
    private const TYPE_STREET_ADDRESS = 1;
    private const VALID_LANGUAGES = ["FI", "EN", "SE"];

    private static ?Client $client = null;

    private ICache $cache;

    public function __construct(ICache $cache)
    {
        if (!self::$client) {
            self::$client = new Client([
                'base_uri' => 'https://avoindata.prh.fi/bis/v1/',
            ]);
        }

        $this->cache = $cache;
    }

    /**
     * @throws ValidationException
     * @throws ApiErrorException
     */
    public function fetchInfo(string $businessId)
    {
        if (!self::isValidBusinessId($businessId)) {
            throw new ValidationException;
        }

        $cached = $this->cache->get("PRH:" . $businessId);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = self::$client->request("GET", $businessId);
        } catch (ClientException $e) {
            throw new ApiErrorException;
        }

        if ($response->getStatusCode() !== 200) {
            throw new ApiErrorException;
        }

        try {
            $content = $response->getBody()->getContents();
        } catch (RuntimeException $e) {
            throw new ApiErrorException;
        }

        try {
            $json = json_decode($content, true, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ApiErrorException;
        }

        if (count($json["results"]) < 1) {
            throw new ApiErrorException;
        }

        $result = $json["results"][0];

        $addr = self::findAddress($result);
        $info = array_combine(self::VALID_LANGUAGES, array_map(function (string $lang) use ($result) {
            $info = self::findBusinessLines($result, $lang);

            return $info ? ["code" => $info["code"], "text" => $info["name"]] : null;
        }, self::VALID_LANGUAGES));

        $data = [
            "name" => $result["name"],
            "web" => self::findContactField($result, self::FIELD_WEB),
            "address" => $addr ? ["street" => $addr["street"], "city" => $addr["city"], "postCode" => $addr["postCode"]] : null,
            "info" => $info
        ];

        $this->cache->set("PRH:" . $businessId, $data);

        return $data;
    }

    private static function findContactField(array $data, $type): ?string
    {
        foreach ($data["contactDetails"] as $d) {
            if ($d["type"] === $type && $d["version"] === 1) {
                return $d["value"];
            }
        }

        return null;
    }

    private static function findAddress(array $data): ?array
    {
        foreach ($data["addresses"] as $d) {
            if ($d["version"] === 1 && $d["type"] === self::TYPE_STREET_ADDRESS) {
                return $d;
            }
        }

        return null;
    }

    private static function findBusinessLines(array $data, string $language): ?array
    {
        foreach ($data["businessLines"] as $d) {
            if ($d["version"] === 1 && $d["language"] === $language) {
                return $d;
            }
        }

        return null;
    }

    public static function isValidBusinessId(string $businessId): bool
    {
        // TODO: tarkista tarkistusmerkki
        return preg_match("/^[0-9]{7}\-[0-9]$/", $businessId) === 1;
    }

    private static function isValidLanguage(string $lang): bool
    {
        return in_array($lang, self::VALID_LANGUAGES);
    }
}
