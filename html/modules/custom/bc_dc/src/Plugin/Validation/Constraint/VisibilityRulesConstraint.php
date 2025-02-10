<?php

namespace Drupal\bc_dc\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the combination of 'Visibility' values is acceptable.
 *
 * Validate 'Visibility' so that a user can only apply:
 *   - Public on its own, or
 *   - IDIR on its own, or
 *   - one or more Branch domains (not in combination with Public or IDIR)
 *
 * @Constraint(
 *   id = "bc_dc_VisibilityRules",
 *   label = @Translation("Visibility choice-Constraint", context = "Validation"),
 *   type = "string"
 * )
 */
class VisibilityRulesConstraint extends Constraint {

  /**
   * Constraint violation message for choosing other visibility
   *   at the same time as 'public' or 'IDIR users'.
   *
   * @var string
   */
  public $onlyPublicOrIDIRMessage = "If you choose '%public_or_idir' visibility, you may not also choose other levels of visibility.";

}
