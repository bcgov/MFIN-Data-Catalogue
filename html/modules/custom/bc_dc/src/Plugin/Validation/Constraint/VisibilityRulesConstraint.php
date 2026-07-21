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
  public $onlyPublicOrIDIRMessage = "When %public_or_idir visibility is selected, no other visiblity levels can be selected.";

}
