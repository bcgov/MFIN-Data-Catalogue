<?php

namespace Drupal\bc_dc\Plugin\views\field;

/**
 * Field handler for build button.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("bc_dc_build")
 */
class BcDcBuild extends BcDcButtonBase {

  /**
   * {@inheritdoc}
   */
  protected function buttonConfig(): array {
    return [
      'text' => $this->t('Build'),
      'class' => 'btn-primary',
      'route' => 'bc_dc.data_set_build_page_tab',
    ];
  }

}
