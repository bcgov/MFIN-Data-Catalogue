<?php

namespace Drupal\cs_notify\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;

class NotificationQueueService {

  public function __construct(
      LoggerChannelFactoryInterface $loggerFactory,
      QueueFactory $queueFactory,
  ) {
    $this->logger = $loggerFactory->get('cs_notify');
    $this->queueFactory = $queueFactory;
  }

  protected $logger;
  protected $queueFactory;

  /**
   * Queue up an email to be processed/sent later (by cron).
   */
  function queueEmail(array $to_addresses, string $subject, string $body) {
    $this->queueFactory         
      ->get('cs_notify_email_queue')  // returns a QueueInterface
      ->createItem(
        [
          'to_addresses' => $to_addresses,
          'subject' => $subject,
          'body' => $body,
        ]
      )
    ;

    $this->logger->info('Queued up email to send to @addresses', 
      ['@addresses' => implode(', ', $to_addresses)]
    );
  }

}