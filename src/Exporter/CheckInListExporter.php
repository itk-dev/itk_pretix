<?php

namespace Drupal\itk_pretix\Exporter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Check-in list exporter.
 */
class CheckInListExporter extends AbstractExporter {
  /**
   * {@inheritdoc}
   */
  protected static $id = 'checkinlist';

  /**
   * {@inheritdoc}
   */
  protected static $name = 'Check-in list';

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildForm(array $form, FormStateInterface $form_state) {
    $formats = [
      'xlsx' => $this->t('Excel (.xlsx)'),
      'default' => $this->t('CSV (with commas)'),
      'csv-excel' => $this->t('CSV (Excel-style)'),
      'semicolon' => $this->t('CSV (with semicolons)'),
    ];

    return [
      '_format' => [
        '#type' => 'select',
        '#title' => $this->t('Format'),
        '#options' => $formats,
        '#default_value' => 'xlsx',
      ],
      'hidden_elements' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['visually-hidden']],
        'list' => $this->buildCheckInListField(),
        'questions' => $this->buildQuestionsField(),
      ],
    ];
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
