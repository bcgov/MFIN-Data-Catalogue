<?php

namespace Drupal\cs_notify\Other;

final class NotificationResult {

  public function __construct(
    public readonly bool $accepted,
    public readonly ?string $messageId = NULL,
    public readonly ?string $errorMessage = NULL,
  ) {}

}
