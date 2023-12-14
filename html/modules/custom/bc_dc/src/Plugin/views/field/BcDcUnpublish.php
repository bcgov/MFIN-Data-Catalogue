<?php

namespace Drupal\bc_dc\Plugin\views\field;

/**
 * Field handler for unpublish button.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("bc_dc_unpublish")
 */
class BcDcUnpublish extends BcDcButtonBase {

  /**
   * {@inheritdoc}
   */
  protected function buttonConfig(): array {
    return [
      'text' => $this->t('Unpublish'),
      'class' => 'btn-secondary',
      'route' => 'bc_dc.data_set_archive_page',
    ];
  }

}
