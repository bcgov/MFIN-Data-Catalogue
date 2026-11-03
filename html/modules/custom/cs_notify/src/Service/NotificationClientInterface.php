<?php

namespace Drupal\cs_notify\Service;


interface NotificationClientInterface {

  /**
   * Add an email to a Drupal Queue, to be sent later via the Notify API.
   */
  public function sendEmailQueued(
    array    $to_addresses,   // array of strings, e.g. ['addr1@test.com', 'addr2@other.com']
    string   $subject, 
    string   $body
  );

}