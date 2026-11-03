<?php

namespace Drupal\cs_notify\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\cs_notify\Exception\NotifyAPIException;
use GuzzleHttp\ClientInterface;

class NotificationClient implements NotificationClientInterface {

  public function __construct(
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    protected QueueFactory $queueFactory,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('cs_notify');
  }

  /**
   * Logger channel.
   */
  protected $logger;

  /**
   * Queue up an email to be processed/sent later (by cron).
   */
  public function sendEmailQueued(array $to_addresses, string $subject, string $email_body) {
    $this->queueFactory
      ->get('cs_notify_email_to_hand_off')
      ->createItem([
        'to_addresses' => $to_addresses,
        'subject' => $subject,
        'body' => $email_body,
      ]);

    $this->logger->info('Queued up email to send to @addresses',
      ['@addresses' => implode(', ', $to_addresses)]
    );
  }

  /**
   * Send an email (right now), using the Notify API, from Connected Services.
   * 
   * This function is 'blocking' meaning that while you're waiting for the hand-off
   * to the Notify service, your code (and the person loading the webpage) is waiting.
   */
  public function sendEmailImmediateBlocking(array $to_addresses, string $subject, string $email_body) {

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
        $request_payload + ['http_errors' => FALSE],  // Affects how Guzzle handles 4xx and 5xx responses. We want to handle those ourselves.
      );

      $httpResponseCode = (int) $response->getStatusCode();

      switch ($httpResponseCode) {

        case 202: return; // Accepted by Notify. 'return' dequeues the item.

        // We don't want to retry sending this email, but we do want to log the error.
        case 400: $this->logger->error('Notify rejected request: 400'); return;
        case 401: $this->logger->error('Notify API key invalid: 401');  return;
        case 403: $this->logger->error('Notify tenant mismatch: 403');  return;
        case 422: $this->logger->error('Notify validation failure: @payload',
            ['@payload' => print_r($request_payload, TRUE)]);           return;

        // Rate-limit exceeded. 
        // - Suspend attempts to process the queue for a while, but try again later.
        case 429: throw new SuspendQueueException('Notify rate-limit exceeded.');

        // Notify is having a problem, but it might be temporary. 
        // - Suspend attempts to process the queue for a while, but try again later.
        case 500: throw new SuspendQueueException('Notify service: "Internal server error"');
        case 502: throw new SuspendQueueException('Notify service: "Bad gateway"');
        case 503: throw new SuspendQueueException('Notify service: "Service unavailable"');
        case 504: throw new SuspendQueueException('Notify service: "Gateway timeout"');
        default:  throw new SuspendQueueException("Unexpected response code from Notify service: {$httpResponseCode}");
      }

    }
    catch (\GuzzleHttp\Exception\ConnectException  $e) { throw new SuspendQueueException('Notify problem: DNS failure, timeout, connection refused, etc.' . $e->getMessage()); }
    catch (\GuzzleHttp\Exception\TransferException $e) { throw new SuspendQueueException('Notify problem: Other transport-level issues.' .                  $e->getMessage()); }
    catch (\GuzzleHttp\Exception\GuzzleException   $e) { throw new SuspendQueueException('Unexpected Guzzle error: ' .                                      $e->getMessage()); } // Should rarely be reached, but acts as a safety net.

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
    $this->logger->info('Notify API says: "@status", "@message", for messageId @id', [
      '@id' => $messageId, 
      '@status' => $response_body['status'] ?? 'unknown', 
      '@message' => $response_body['message'] ?? 'no message found'
    ]);

    // Queue up a follow-up task to check the status of this email, based on the message ID returned by the Notify API.
    $this->queueFactory
      ->get('cs_notify_email_status_check')
      ->createItem([
        'notify_id' => $messageId,
      ]);


  }

}