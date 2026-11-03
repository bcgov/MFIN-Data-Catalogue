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
use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException as GuzzleTransferException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Check on the status of a previously sent email, using the Notify API, from Connected Services.
 *
 * 'cron' will pick this up automatically,
 * quitting after about 40 seconds of checking.
 *
 * @QueueWorker(
 *   id = "cs_notify_email_status_check",
 *   title = @Translation("Check on status of emails being sent by Connected Services Notify (QueueWorker)"),
 *   cron = {"time" = 40}
 * )
 */
class NotificationStatusCheckQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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
      'timeout' => $config->get('timeout'),
    ];

    try {
      $url = sprintf('%s/api/v1/notification_request/%s/request_details', $config->get('api_url'), $queuedEmail['notify_id']);
      $response = $this->httpClient->request(
        'GET',
        $url,
        $request_payload + ['http_errors' => FALSE], // Affects how Guzzle handles 4xx and 5xx responses. We want to handle those ourselves.
      );

    }
    catch (GuzzleConnectException $e) {
      throw new SuspendQueueException('Notify problem: DNS failure, timeout, connection refused, etc. ' . $e->getMessage(), 0, $e);
    }
    catch (GuzzleTransferException $e) {
      throw new SuspendQueueException('Notify problem: Other transport-level issues. ' . $e->getMessage(), 0, $e);
    }
    catch (GuzzleException $e) {
      // Should rarely be reached, but acts as a safety net.
      throw new SuspendQueueException('Unexpected Guzzle error: ' . $e->getMessage(), 0, $e);
    }

    $httpResponseCode = $response->getStatusCode();

    try {
      switch ($httpResponseCode) {

        case 200:
          // Information about the notification request was returned.
          // - Carry on below to log the result and queue a status check.
          break;

        // Bad errors. No sense retrying these ones.
        case 404: throw new NotifyHandoffPermanentFailException('No such notification for this tenant: 404');

        // Something we messed up with Notify setup. Could try again later.
        case 401: throw new NotifyHandoffTemporaryFailException('Notify API key invalid: 401');
        case 403: throw new NotifyHandoffTemporaryFailException('Notify tenant mismatch: 403');

        // Notify is having a problem, but it might be temporary.
        case 500: throw new NotifyHandoffTemporaryFailException('Notify service: "Internal server error"');
        case 502: throw new NotifyHandoffTemporaryFailException('Notify service: "Bad gateway"');
        case 503: throw new NotifyHandoffTemporaryFailException('Notify service: "Service unavailable"');
        case 504: throw new NotifyHandoffTemporaryFailException('Notify service: "Gateway timeout"');
        default:  throw new NotifyHandoffTemporaryFailException("Unexpected response code from Notify service: {$httpResponseCode}");   
      }
      
      $response_body = json_decode((string) $response->getBody(), TRUE);
      /* Response body looks something like this:
        
          [
            {
              "id": "5bd6b793-affa-4b43-b36a-57da9161e377",
              "notificationRequestId": "8e269cb1-6518-4f04-9997-fdf45537fcb4",
              "batchId": null,
              "recipientAddress": "dan.howard@gov.bc.ca",
              "channel": "EMAIL",
              "emailAddressType": "primary",
              "status": "sent",
              "providerResponseId": "5601780e-a3cd-45da-a34c-e568de177a84",
              "providerResponse": null,
              "params": null,
              "errorMessage": null,
              "attemptCount": 1,
              "lastAttemptAt": "2026-11-03T00:11:29.692Z",
              "createdAt": "2026-11-03T00:11:29.511Z",
              "createdBy": "8e0d83cd-827b-4fd2-8049-4a2a8720f9bd",
              "updatedAt": "2026-11-03T00:11:29.693Z",
              "updatedBy": "system",
              "notificationRequest": {
                "id": "8e269cb1-6518-4f04-9997-fdf45537fcb4",
                "tenantId": "8e0d83cd-827b-4fd2-8049-4a2a8720f9bd",
                "status": "completed",
                "channelCodes": [
                  "EMAIL"
                ],
                "requestRoute": "/notifysimple",
                "delayedSendTime": null,
                "recipients": {
                  "email": [
                    "dan.howard@gov.bc.ca"
                  ]
                },
                "payload": {
                  "email": {
                    "content": {
                      "body": "<h2>Hello</h2><p>Your application has been received.</p>",
                      "subject": "Your queued application",
                      "bodyType": "html"
                    },
                    "recipients": {
                      "to": [
                        "dan.howard@gov.bc.ca"
                      ]
                    }
                  }
                },
                "eventId": null,
                "isInternal": false,
                "createdAt": "2026-11-03T00:11:29.478Z",
                "createdBy": "appproxy",
                "updatedAt": "2026-11-03T00:11:29.698Z",
                "updatedBy": "appproxy",
                "errorReason": null,
                "quarantineDetails": null,
                "tenant": {
                  "id": "8e0d83cd-827b-4fd2-8049-4a2a8720f9bd",
                  "externalId": "47932d18-498d-4395-ab34-2bf424a03f92",
                  "name": "MFIN Data Catalogue",
                  "slug": "mfin-data-catalogue",
                  "status": "active",
                  "createdAt": "2026-11-02T21:15:58.580Z",
                  "createdBy": "CSTAR_SYNC",
                  "updatedAt": "2026-11-03T16:10:27.462Z",
                  "updatedBy": null,
                  "isDeleted": false
                },
                "statusCode": {
                  "code": "completed",
                  "description": "Notification was successfully delivered",
                  "displayName": "Completed",
                  "createdAt": "2026-04-21T17:43:40.254Z",
                  "createdBy": "system",
                  "updatedAt": "2026-04-21T17:43:40.254Z",
                  "updatedBy": null,
                  "sort_order": 70
                }
              }
            }
          ]        

      */
          
      $status = $response_body[0]['status'];

      if (empty($status)) {
        throw new NotifyHandoffTemporaryFailException('Notify API did not return a status.');
      }
      switch ($status) {
        case 'accepted':
        case 'processing':
        case 'sending':
          // Email has not actually been sent yet. Check on it again later.
          throw new NotifyHandoffTemporaryFailException("Email still at '{$status}' status. Not quite sent yet.");

        case 'sent':
        case 'completed':
          $this->logger->info('Email "@status", for messageId @id', [
            '@id' => $queuedEmail['notify_id'],
            '@status' => $status,
          ]);
          return; // Done with this item, remove it from the queue.

        case 'failed':
        case 'bounced':
        case 'rejected':
          throw new NotifyHandoffPermanentFailException("Email fail: {$status}");
        default:
          // Email is still being processed, or Notify is having a problem. Try again later.
          throw new NotifyHandoffTemporaryFailException("Unknown status '{$status}' from Notify API");
      }
      
    }
    catch (NotifyHandoffPermanentFailException $e) {
      // Log the error, and return to remove item from the queue.
      $this->logger->error('Perm-fail: @message -- for messageId @id', [
        '@message' => $e->getMessage(), 
        '@id' => $queuedEmail['notify_id']
      ]);
      return;
    }
    catch (NotifyHandoffTemporaryFailException $e) {
      $this->logger->warning('Temp-fail: @message -- for messageId @id', [
        '@message' => $e->getMessage(),
        '@id' => $queuedEmail['notify_id']
      ]);
      // Rethrow as a SuspendQueueException to stop processing the queue for now, so it is tried again on a later cron run.
      throw new SuspendQueueException('Notify API temporary fail: ' . $e->getMessage(), 0, $e);
    }



  }

}