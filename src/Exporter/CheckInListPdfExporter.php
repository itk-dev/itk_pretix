<?php

namespace Drupal\itk_pretix\Exporter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Check-in list exporter.
 */
class CheckInListPdfExporter extends CheckInListExporter {
  /**
   * {@inheritdoc}
   */
  protected static $id = 'checkinlistpdf';

  /**
   * {@inheritdoc}
   */
  protected static $name = 'Check-in list (PDF)';

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    unset($form['_format']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function processInputParameters(array $parameters) {
    $parameters = parent::processInputParameters($parameters);
    // Make sure that questions is set.
    $parameters += ['questions' => []];

    return $parameters;
  }

}
