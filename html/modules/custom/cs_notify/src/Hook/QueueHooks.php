<?php

namespace Drupal\cs_notify\Hook;

use Drupal\Core\Hook\Attribute\Hook;

class QueueHooks {

  /**
   * Turn off cron queue processing for our queues if the config setting is enabled.
   * 
   * If running on Openshift, we often use a Deployment that 
   * runs cron jobs and ALSO runs queue processing. 
   * We don't want to run queue processing in both places, to make our 
   * log messages clearer.
   * 
   * This is the equivalent of 'hook_queue_info_alter' in Drupal 7.
   */
  #[Hook('queue_info_alter')]
  public function queueInfoAlter(array &$queues): void {
    $config = \Drupal::config('cs_notify.settings');
    if ($config->get('disable_cron_queue_processing')) {
      $queues_to_disable = ['cs_notify_email_to_hand_off', 'cs_notify_email_status_check'];
      foreach ($queues_to_disable as $queue_name) {
        if (isset($queues[$queue_name])) {
          unset($queues[$queue_name]['cron']);
        }
      }
    }
  }

}