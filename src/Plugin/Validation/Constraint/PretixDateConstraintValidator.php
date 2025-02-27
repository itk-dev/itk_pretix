<?php

namespace Drupal\itk_pretix\Plugin\Validation\Constraint;

use Drupal\itk_pretix\Plugin\Field\FieldType\PretixDate;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the PretixValidTimeTo constraint.
 */
class PretixDateConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function validate($item, Constraint $constraint) {
    if (!$item instanceof PretixDate || !$constraint instanceof PretixDateConstraint) {
      return;
    }

    $timeFrom = $item->time_from;
    $timeTo = $item->time_to;
    if ($timeTo && $timeFrom && $timeTo < $timeFrom) {
      $this->context
        ->buildViolation($constraint->timeToBeforeTimeFrom, [
          '%time_to' => $timeTo->format(\DateTimeInterface::ATOM),
          '%time_from' => $timeFrom->format(\DateTimeInterface::ATOM),
        ])
        ->atPath('time_to_value')
        ->addViolation();
    }
  }

}
