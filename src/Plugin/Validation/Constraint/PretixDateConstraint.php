<?php

namespace Drupal\itk_pretix\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted time to is not before time from.
 *
 * @Constraint(
 *   id = "PretixDateConstraint",
 *   label = @Translation("Pretix date constraint", context = "Validation"),
 *   type = "pretix_date"
 * )
 */
class PretixDateConstraint extends Constraint {
  /**
   * Validate error message.
   *
   * @var string
   */
  public $timeToBeforeTimeFrom = 'The end time cannot be before the start time';

}
