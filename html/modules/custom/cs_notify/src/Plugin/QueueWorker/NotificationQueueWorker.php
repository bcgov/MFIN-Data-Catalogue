<?php

namespace Drupal\cs_notify\Plugin\QueueWorker;

use Drupal\cs_notify\Exception\NotifyAPIException;
use Drupal\cs_notify\Other\NotificationResult;
use Drupal\Core\Queue\QueueWorkerBase;
use Psr\Log\LoggerInterface;

/**
 * Process items from our email queue.
 * 
 * 'cron' will pick this up automatically, 
 * quitting after about 40 seconds of mailing.
 *
 * @QueueWorker(
 *   id = "cs_notify_email_queue",
 *   title = @Translation("Send emails, Connected Services Notify (QueueWorker)"),
 *   cron = {"time" = 40}
 * )
 */
class NotificationQueueWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($queuedEmail) {
    $logger = \Drupal::logger('cs_notify');
    try {
      $notifyResult = \Drupal::service('cs_notify.client')->sendEmailImmediateBlocking(
        $queuedEmail['to_addresses'],
        $queuedEmail['subject'],
        $queuedEmail['body']
      );
    }
    catch (NotifyAPIException $e) {
      switch ($e->getCode()) {

        case 400:  // Malformed request.
        case 422:  // Well-formed request, but can't be accepted.
          $logger->error("Can't send this email: " . $e->getMessage());
          // No rethrow, means Queue API removes this item from the queue.
          break;

        case 401:  // Missing or invalid API key
        case 403:  // API key is not bound to the requested tenant
          $logger->error("Can't send any emails: " . $e->getMessage());
          // This exception means "try this item again later, but stop trying any items right now".
          throw new SuspendQueueException("System-level problem trying to send emails. Try again later.", $e->getCode(), $e);

      }
    }
  }
}
