<?php

namespace Drupal\cs_notify\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\cs_notify\Other\NotificationResult;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class NotificationClient implements NotificationClientInterface {

  public function __construct(
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('cs_notify');
  }

  /**
   * Logger channel.
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function sendEmail(array $to_addresses, string $subject, string $body) : NotificationResult {

    $config = $this->configFactory->get('cs_notify.settings');

    try {
      $response = $this->httpClient->request('POST', $config->get('api_url') . '/api/v1/notifysimple', [
        'headers' => [
          'X-Api-Key' => $config->get('api_key'),
          'Accept' => 'application/json',
        ],
        'json' => [
          'email' => [
              'recipients' => [
                  'to' => $to_addresses,
              ],
              'content' => [
                  'subject' => $subject,
                  'body' => $body,
                  'bodyType' => 'html',
              ],
          ],
        ],
        'timeout' => $config->get('timeout'),
      ]);
      $body = json_decode((string) $response->getBody(), TRUE);
      /* Response body looks something like this:
        {
          "notifyId": "d411312b-26c0-449f-baf9-6a8ad15f0e34",
          "status": "accepted",
          "channels": [
            "email"
          ],
          "createdAt": "2026-09-24T19:31:35.819Z",
          "message": "Notification accepted, queuing in progress"
        }
      */
      $messageId = $body['notifyId'] ?? 'no ID found';
      $this->logger->info('Notification accepted. Message ID: @id', ['@id' => $messageId]);
      return new NotificationResult(TRUE, $messageId);
    }
    catch (GuzzleException $e) {
      $this->logger->error('CS Notify request failed: @message', ['@message' => $e->getMessage()]);
      return new NotificationResult(FALSE, NULL, $e->getMessage());
    }

  }

}