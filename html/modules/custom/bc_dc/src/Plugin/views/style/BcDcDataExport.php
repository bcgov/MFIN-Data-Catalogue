<?php

namespace Drupal\bc_dc\Plugin\views\style;

use Drupal\Core\Url;
use Drupal\views_data_export\Plugin\views\style\DataExport;

/**
 * A style plugin for data export views.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "bc_dc_data_export",
 *   title = @Translation("BCDC Data export"),
 *   help = @Translation("Configurable row output for data exports."),
 *   display_types = {"data"}
 * )
 */
class BcDcDataExport extends DataExport {

  /**
   * {@inheritdoc}
   */
  public function attachTo(array &$build, $display_id, Url $url, $title): void {
    // On the information schedule report, the URL contain a huge filter which
    // causes the URL to be too big. Set that filter parametre to be empty. This
    // fixes the URL. The needed filtering still happens.
    // @see https://www.drupal.org/project/views_data_export/issues/3431433
    if ($url->getRouteName() === 'view.information_management_report.information_schedule_export') {
      $url->setRouteParameter('arg_1', '');
    }

    parent::attachTo($build, $display_id, $url, $title);
  }

}
