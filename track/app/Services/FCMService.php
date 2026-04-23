<?php
namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Psr\Log\LoggerInterface;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\ApnsPayload;
use Kreait\Firebase\Messaging\Aps;

class FCMService
{
    protected $messaging;
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->messaging = (new Factory)
            ->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')))
            ->createMessaging();

        $this->logger = $logger;
    }

    /**
     * Send push to a single token (notification + data)
     */
    public function sendToToken(?string $token, string $title, string $body, array $data = [])
    {
        if (!$token) {
            $this->logger->warning('FCM token empty, skipping send');
            return null;
        }

        try {

            // Android sound config
            $androidConfig = AndroidConfig::fromArray([
                'notification' => [
                    'sound' => 'default',
                ],
                'priority' => 'high',
            ]);

            // iOS sound config
            $apnsConfig = ApnsConfig::fromArray([
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                    ],
                ],
            ]);

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withAndroidConfig($androidConfig)
                ->withApnsConfig($apnsConfig)
                ->withData($data);

            return $this->messaging->send($message);

        } catch (\Throwable $e) {
            $this->logger->error('FCM send error', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
