<?php

namespace Drupal\cs_notify\Service;

use Drupal\cs_notify\Other\NotificationResult;

interface NotificationClientInterface {

  /**
   * Send an email through Connected Services 'Notify'.
   */
  public function sendEmail(
    array    $to_addresses,   // array of strings, e.g. ['addr1@test.com', 'addr2@other.com']
    string   $subject, 
    string   $body
  ): NotificationResult;

}