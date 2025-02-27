<?php

namespace Drupal\itk_pretix\Exporter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Order list exporter.
 */
class OrderListExporter extends AbstractExporter {
  /**
   * {@inheritdoc}
   */
  protected static $id = 'orderlist';

  /**
   * {@inheritdoc}
   */
  protected static $name = 'Order data';

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildForm(array $form, FormStateInterface $form_state) {
    $formats = [
      'xlsx' => 'xlsx',
      'orders:default' => 'orders:default',
      'orders:excel' => 'orders:excel',
      'orders:semicolon' => 'orders:semicolon',
      'positions:default' => 'positions:default',
      'positions:excel' => 'positions:excel',
      'positions:semicolon' => 'positions:semicolon',
      'fees:default' => 'fees:default',
      'fees:excel' => 'fees:excel',
      'fees:semicolon' => 'fees:semicolon',
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
        'list' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Paid only'),
          '#default_value' => FALSE,
        ],
      ],
    ];
  }

}
