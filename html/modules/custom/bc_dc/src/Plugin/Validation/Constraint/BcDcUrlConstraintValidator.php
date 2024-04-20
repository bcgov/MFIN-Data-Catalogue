<?php

namespace Drupal\bc_dc\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the BcDcUrlConstraint constraint.
 */
class BcDcUrlConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    // This will be a string in kernel tests.
    if (is_object($value)) {
      $url = $value->field_paragraph_document_link?->value;
      // Validate based on version that has gone through trim(). It is saved
      // trimmed in bc_dc_paragraph_presave().
      $url = $url ? trim($url) : NULL;
    }
    else {
      $url = (string) $value;
    }

    // If there is a URL, it must pass parse_url() and the regex.
    if ($url && (parse_url($url) === FALSE || !preg_match(',^(//|\\\\|(file|https?|ftps?):),', $url))) {
      $this->context->addViolation($constraint->message);
    }
  }

}
