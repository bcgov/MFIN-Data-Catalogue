<?php

namespace Drupal\bc_dc\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validate 'Visibility' so that a user can only apply...
 *
 * Public on its own, or
 * IDIR on its own, or
 * one or more Branch domains (not in combination with Public or IDIR)
 */
class VisibilityRulesConstraintValidator extends ConstraintValidator {

  /**
   * Checks that the combination of 'Visibility' values is acceptable.
   *
   * @param mixed $visibility_field
   *   The field/value that should be validated.
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   The constraint for the validation.
   */
  public function validate($visibility_field, Constraint $constraint) {
    $taxonomy_term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    // There are three visibility types we care about: Public, IDIR, and 'other'.
    // We'll see which of those three have been chosen for this node.
    $visibility_types = [];
    // They may have chosen several visibility types. Loop through them.
    foreach ($visibility_field->getValue() as $visibility_type_term_info) {
      $visibility_type_term_id = $visibility_type_term_info['target_id'];
      // A visibility type term is something like 'Public' or 'Income Taxation Branch'.
      $visibility_type_term = $taxonomy_term_storage->load($visibility_type_term_id);
      // Each of these visibility type terms has an 'access' field.
      // Usually it's empty, but Public is 'pub' and IDIR is 'auth'.
      $access_type_string = $visibility_type_term->field_access_flag->value ?: 'other';
      $visibility_types[$access_type_string] = $visibility_type_term->getName();
    }

    // If the user has chosen Public as well as anything else, that is a problem.
    // Same as IDIR and anything else.
    foreach (['pub', 'auth'] as $access_type) {
      if (isset($visibility_types[$access_type]) && count($visibility_types) > 1) {
        $this->context->addViolation($constraint->onlyPublicOrIDIRMessage, [
          '%public_or_idir' => $visibility_types[$access_type],
        ]);
      }
    }
  }

}
