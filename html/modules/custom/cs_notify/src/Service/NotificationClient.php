<?php

namespace Drupal\cs_notify\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\cs_notify\Other\NotificationResult;
use Drupal\cs_notify\Exception\NotifyAPIException;
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
   * Send an email (right now), using the Notify API, from Connected Services.
   */
  public function sendEmail(array $to_addresses, string $subject, string $email_body) : NotificationResult {

    $config = $this->configFactory->get('cs_notify.settings');

    $request_payload = [
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
                'body' => $email_body,
                'bodyType' => 'html',
            ],
        ],
      ],
      'timeout' => $config->get('timeout'),
    ];

    try {
      $response = $this->httpClient->request(
        'POST', 
        $config->get('api_url') . '/api/v1/notifysimple', 
        $request_payload
      );

      $httpResponseCode = (int) $response->getStatusCode();
      switch ($httpResponseCode) {
        case 202: 
          // Accepted for delivery. The notifyId identifies this request from here on.
          break;

        case 400: throw new NotifyAPIException(
          "Malformed request, or no channel supplied. Request payload: " . print_r($request_payload, true),
          400);

        case 401: throw new NotifyAPIException("Missing or invalid API key", 401);

        case 403: throw new NotifyAPIException("The API key is not bound to the requested tenant", 403);

        case 422: throw new NotifyAPIException(
          "The request is well formed but cannot be accepted - for example both a templateId and inline content," 
            . " an unknown templateId, or a recipient blocked by the safelist."
            . " Request payload: " . print_r($request_payload, true),
          422);

        default: throw new NotifyAPIException("Unknown code", $httpResponseCode);
      }


      $response_body = json_decode((string) $response->getBody(), TRUE);
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

      $messageId = $response_body['notifyId'] ?? 'no ID found';
      $this->logger->info('Notification accepted. Message ID: @id', ['@id' => $messageId]);
      return new NotificationResult(TRUE, $messageId);
    }
    catch (GuzzleException $e) {
      $this->logger->error('CS Notify request failed: @message', ['@message' => $e->getMessage()]);
      return new NotificationResult(FALSE, NULL, $e->getMessage());
    }

  }

}