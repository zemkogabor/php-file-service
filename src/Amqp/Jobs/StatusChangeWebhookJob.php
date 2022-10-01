<?php

declare(strict_types = 1);

namespace Acme\Amqp\Jobs;

use Acme\Base\Base;
use Acme\File\File;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

class StatusChangeWebhookJob extends Job
{
    protected const MAX_RETRIES = 5;

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'status_change_webhook';
    }

    /**
     * @inheritdoc
     * @throws \DBLaci\Data\EtalonInstantiationException
     */
    public function process(array $data): void
    {
        $webhook = getenv('STATUS_CHANGE_WEBHOOK');

        if ($webhook === false) {
            throw new \LogicException('Status change webhook is not set.');
        }

        $file = File::getInstanceByID($data['fileId']);

        for ($retryCount = 1; $retryCount <= static::MAX_RETRIES; $retryCount++) {
            $client = new Client();
            $request = [
                'downloadUuid' => $file->download_uuid,
                'status' => $file->getStatusForClient(),
            ];

            $warningMessage = 'Status change webhook failed. (' . static::MAX_RETRIES - $retryCount . ' retries left)';

            try {
                $response = $client->post($webhook, [
                    RequestOptions::JSON => $request,
                    RequestOptions::TIMEOUT => 5,
                ]);
            } catch (GuzzleException $e) {
                Base::getLogger()->warning($warningMessage, [
                    'message' => $e->getMessage(),
                ]);
                continue;
            }

            if ($response->getStatusCode() === 200) {
                Base::getLogger()->info('Status change webhook success.');
                return;
            }

            Base::getLogger()->warning($warningMessage, [
                'statusCode' => $response->getStatusCode(),
            ]);

            sleep(10);
        }

        Base::getLogger()->error('Status change webhook failed.');
    }
}
