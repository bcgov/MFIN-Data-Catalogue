<?php

namespace Drupal\bc_dc\Controller;

use Drupal\Core\Controller\ControllerBase;

class LoggingTestController extends ControllerBase {

  /**
   * Messenger method to call for each log level.
   */
  const MESSENGER_METHODS = [
    'info' => 'addMessage',
    'notice' => 'addMessage',
    'warning' => 'addWarning',
    'error' => 'addError',
    'critical' => 'addError',
    'alert' => 'addError',
  ];

  public function logTest(string $level) {
    $message = sprintf('A test %s-level event was logged at %s.', $level, date('H:i:s'));

    $this->getLogger('bc_dc')->log($level, $message);

    $messenger_method = self::MESSENGER_METHODS[$level];
    $this->messenger()->$messenger_method($message);

    return [];
  }

}
