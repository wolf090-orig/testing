<?php

namespace app\clients;

use app\classes\Interfaces\MsProfileInterface;
use GuzzleHttp\Client;

class MsProfile implements MsProfileInterface
{
    public const string PROFILE_URI_PREFIX = "/api/v1/profile";

    public Client $client;
    public ?int $id;
    public bool $isIdentified;

    public function __construct()
    {
        $config = config('integrations.ms_profile');
        $this->id = 0;
        $this->client = new Client([
            'base_uri' => $config['base_uri'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => $config['token']
            ]
        ]);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): int
    {
        $this->id = $id;
        return $this->id;
    }

    public function getByUserId(int $userId): MsProfileInterface
    {
        $response = $this->client->get(self::PROFILE_URI_PREFIX . '/user', [
            'query' => [
                'user_id' => $userId
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $profile = $data['data'];

        $this->id = $profile['id'];
        $this->isIdentified = $profile['is_identified'] ?? false;

        return $this;
    }

    public function isIdentified(): bool
    {
        return $this->isIdentified;
    }
}
