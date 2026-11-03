<?php

namespace Drupal\cs_notify\Plugin\QueueWorker;

use Drupal\cs_notify\Exception\NotifyHandoffPermanentFailException;
use Drupal\cs_notify\Exception\NotifyHandoffTemporaryFailException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Process items from our email queue.
 *
 * 'cron' will pick this up automatically,
 * quitting after about 40 seconds of mailing.
 *
 * @QueueWorker(
 *   id = "cs_notify_email_to_hand_off",
 *   title = @Translation("Send emails, Connected Services Notify (QueueWorker)"),
 *   cron = {"time" = 40}
 * )
 */
class NotificationHandOffQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Logger channel.
   */
  protected LoggerInterface $logger;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    protected QueueFactory $queueFactory,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $loggerFactory->get('cs_notify');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('queue'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Send one queued email using the Notify API, from Connected Services.
   *
   * Returning normally removes the item from the queue. Throwing
   * SuspendQueueException releases the item and stops processing this queue
   * for now, so it is tried again on a later cron run.
   *
   * {@inheritdoc}
   */
  public function processItem($queuedEmail) {

    $config = $this->configFactory->get('cs_notify.settings');

    $request_payload = [
      'headers' => [
        'X-Api-Key' => $config->get('api_key'),
        'Accept' => 'application/json',
      ],
      'json' => [
        'email' => [
          'recipients' => [
            'to' => $queuedEmail['to_addresses'],
          ],
          'content' => [
            'subject' => $queuedEmail['subject'],
            'body' => $queuedEmail['body'],
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
        $request_payload + ['http_errors' => FALSE], // Affects how Guzzle handles 4xx and 5xx responses. We want to handle those ourselves.
      );

    }
    catch (ConnectException $e) {
      throw new SuspendQueueException('Notify problem: DNS failure, timeout, connection refused, etc. ' . $e->getMessage(), 0, $e);
    }
    catch (TransferException $e) {
      throw new SuspendQueueException('Notify problem: Other transport-level issues. ' . $e->getMessage(), 0, $e);
    }
    catch (GuzzleException $e) {
      // Should rarely be reached, but acts as a safety net.
      throw new SuspendQueueException('Unexpected Guzzle error: ' . $e->getMessage(), 0, $e);
    }

    $httpResponseCode = $response->getStatusCode();

    try {
      switch ($httpResponseCode) {

        case 202:
          // Accepted by Notify. Carry on below to log the result and queue a status check.
          break;

        // Bad errors. No sense retrying these ones.
        case 400: throw new NotifyHandoffPermanentFailException('Notify rejected request: 400');
        case 422: throw new NotifyHandoffPermanentFailException(sprintf('Notify validation failure: 422, subject "%s", response: %s', [
            $queuedEmail['subject'],
            (string) $response->getBody(),
            // Don't include $request_payload: it contains the API key (and the email body).
        ]));

        // Something we messed up with Notify setup. Could try again later.
        case 401: throw new NotifyHandoffTemporaryFailException('Notify API key invalid: 401');
        case 403: throw new NotifyHandoffTemporaryFailException('Notify tenant mismatch: 403');

        // Rate-limit exceeded... try again later.
        case 429: throw new NotifyHandoffTemporaryFailException('Notify rate-limit exceeded.');

        // Notify is having a problem, but it might be temporary.
        case 500: throw new NotifyHandoffTemporaryFailException('Notify service: "Internal server error"');
        case 502: throw new NotifyHandoffTemporaryFailException('Notify service: "Bad gateway"');
        case 503: throw new NotifyHandoffTemporaryFailException('Notify service: "Service unavailable"');
        case 504: throw new NotifyHandoffTemporaryFailException('Notify service: "Gateway timeout"');
        default:  throw new NotifyHandoffTemporaryFailException("Unexpected response code from Notify service: {$httpResponseCode}");   
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

      $messageId = $response_body['notifyId'];
      if (empty($messageId)) {
        throw new NotifyHandoffTemporaryFailException('Notify API did not return a message ID.');
      }
      
    }
    catch (NotifyHandoffPermanentFailException $e) {
      // Log the error, and return to remove item from the queue.
      $this->logger->error('Notify API permanent fail: @message', ['@message' => $e->getMessage()]);
      return;
    }
    catch (NotifyHandoffTemporaryFailException $e) {
      $this->logger->warning('Notify API temporary fail: @message', ['@message' => $e->getMessage()]);
      // Rethrow as a SuspendQueueException to stop processing the queue for now, so it is tried again on a later cron run.
      throw new SuspendQueueException('Notify API temporary fail: ' . $e->getMessage(), 0, $e);
    }

    $this->logger->info('Notify API says: "@status", "@message", for messageId @id', [
      '@id' => $messageId,
      '@status' => $response_body['status'] ?? 'unknown',
      '@message' => $response_body['message'] ?? 'no message found',
    ]);

    // Queue up a follow-up task to check the status of this email, based on the message ID returned by the Notify API.
    $this->queueFactory
      ->get('cs_notify_email_status_check')
      ->createItem(['notify_id' => $messageId]);
  }

}