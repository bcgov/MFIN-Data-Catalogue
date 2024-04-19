<?php

namespace Drupal\bc_dc\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that a string is a URL.
 *
 * @Constraint(
 *   id = "BcDcUrlConstraint",
 *   label = @Translation("BCDC URL validator", context = "Validation"),
 *   type = "string"
 * )
 */
class BcDcUrlConstraint extends Constraint {

  /**
   * Constraint message.
   *
   * @var string
   */
  public $message = 'Enter a valid URL.';

}
