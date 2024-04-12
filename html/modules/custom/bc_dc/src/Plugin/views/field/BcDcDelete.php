<?php

namespace Drupal\bc_dc\Plugin\views\field;

/**
 * Field handler for delete button.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("bc_dc_delete")
 */
class BcDcDelete extends BcDcButtonBase {

  /**
   * {@inheritdoc}
   */
  protected function buttonConfig(): array {
    return [
      'text' => $this->t('Delete'),
      'class' => 'btn-danger',
      'route' => 'entity.node.delete_form',
      'destination' => TRUE,
    ];
  }

}
