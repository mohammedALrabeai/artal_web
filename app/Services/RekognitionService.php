<?php

namespace App\Services;

use Aws\Exception\AwsException;
// app/Services/RekognitionService.php
use Aws\Rekognition\RekognitionClient;

class RekognitionService
{
    public function client(): RekognitionClient
    {
        $region = config('services.rekognition.region');
        $key    = config('services.rekognition.key');
        $secret = config('services.rekognition.secret');

        if (empty($region)) {
            throw new \InvalidArgumentException('Region غير مٌعرف. ضَع AWS_DEFAULT_REGION في .env');
        }

        return new RekognitionClient([
            'version' => 'latest',
            'region'  => $region,
            'credentials' => [
                'key'    => $key,
                'secret' => $secret,
            ],
        ]);
    }

    public function ensureCollection(string $collectionId): void
    {
        if (empty($collectionId)) {
            throw new \InvalidArgumentException('Collection ID مفقود. ضَع AWS_REKOGNITION_COLLECTION في .env');
        }

        $client = $this->client();
        try {
            $client->createCollection(['CollectionId' => $collectionId]);
        } catch (\Aws\Exception\AwsException $e) {
            $code = $e->getAwsErrorCode();
            if (!in_array($code, ['ResourceAlreadyExistsException', 'AccessDeniedException'])) {
                throw $e;
            }
        }
    }
}
