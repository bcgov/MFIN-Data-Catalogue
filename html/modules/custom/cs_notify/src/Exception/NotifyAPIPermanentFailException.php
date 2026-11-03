<?php

namespace Drupal\cs_notify\Exception;

/**
 * Exception class to indicate a problem sending emails
 *   via the Notify service, from Connected Services.
 * 
 * "Permanent fail" means the Notify service is having a problem, and it is not likely to be temporary.
 */
class NotifyAPIPermanentFailException extends \RuntimeException {
}