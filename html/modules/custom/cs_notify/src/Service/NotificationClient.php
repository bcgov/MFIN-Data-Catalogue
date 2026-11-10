<?php

namespace Drupal\cs_notify\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Psr\Log\LoggerInterface;

class NotificationClient implements NotificationClientInterface {

  /**
   * Logger channel.
   */
  protected LoggerInterface $logger;

  public function __construct(
    protected QueueFactory $queueFactory,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('cs_notify');
  }

  /**
   * Queue up an email to be processed/sent later (by cron).
   *
   * The actual hand-off to the Notify API happens in
   * \Drupal\cs_notify\Plugin\QueueWorker\NotificationHandOffQueueWorker::processItem().
   */
  public function sendEmailQueued(array $to_addresses, string $subject, string $email_body) : void {
    $item_id = $this->queueFactory
      ->get('cs_notify_email_to_hand_off')
      ->createItem([
        'to_addresses' => $to_addresses,
        'subject' => $subject,
        'body' => $email_body,
      ]);

    if ($item_id !== FALSE) {
      # Success!
      $this->logger->info('Queued up email to send to @addresses',
        ['@addresses' => implode(', ', $to_addresses)]
      );
    }
    else {
      throw new \Exception('Failed to queue up email to send to addresses: ' . implode(', ', $to_addresses));
    }
  }
}