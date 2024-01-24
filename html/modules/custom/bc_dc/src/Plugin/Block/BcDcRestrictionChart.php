<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a classification chart block for the dashboard.
 *
 * @Block(
 *   id = "bc_dc_restriction_chart",
 *   admin_label = @Translation("Dashboard records by retriction chart"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user")
 *   }
 * )
 */
class BcDcRestrictionChart extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

    $labels = [];
    $values = [];

    // Get the number of records for each classification.
    $terms = $termStorage->loadTree('organization');
    foreach ($terms as $term) {
      $labels[] = $term->name;
      $query = $nodeStorage
        ->getQuery()
        ->condition('status', 1)
        ->condition('type', 'data_set')
        ->condition('field_visibility', $term->tid)
        ->accessCheck(FALSE);

      $nids = $query->execute();
      $values[] = count($nids);
    }

    // Define a series to be used.
    $series = [
      '#type' => 'chart_data',
      '#title' => $this->t('Restrictions'),
      '#data' => $values,
      '#color' => '#1d84c3',
    ];

    // Define an x-axis to be used.
    $xaxis = [
      '#type' => 'chart_xaxis',
      '#title' => $this->t('Records'),
      '#labels' => $labels,
    ];

    $build = [
      '#type' => 'chart',
      '#tooltips' => FALSE,
      '#title' => $this->t('Records by restriction'),
      '#chart_type' => 'donut',
      'series' => $series,
      'x_axis' => $xaxis,
      '#raw_options' => [],
      '#prefix' => '<div class="card bcdc-dashboard-card"><div class="card-body">',
      '#suffix' => '</div></div>',
      // e.g. ['chart' => ['backgroundColor' => '#000000']].
    ];

    return $build;
  }

}
