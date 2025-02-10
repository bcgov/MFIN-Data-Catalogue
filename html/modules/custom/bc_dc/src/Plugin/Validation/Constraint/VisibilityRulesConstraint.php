<?php

namespace Drupal\bc_dc\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the combination of 'Visibility' values is acceptable.
 *
 * @Constraint(
 *   id = "bc_dc_VisibilityRules",
 *   label = @Translation("Visibility choice-Constraint", context = "Validation"),
 *   type = "string"
 * )
 */
class VisibilityRulesConstraint extends Constraint {

  /**
   * Constraint violation message.
   *
   * For choosing other visibility along with 'public' or 'IDIR users'.
   *
   * @var string
   */
  public $onlyPublicOrIDIRMessage = "If you choose '%public_or_idir' visibility, you may not also choose other levels of visibility.";

}
