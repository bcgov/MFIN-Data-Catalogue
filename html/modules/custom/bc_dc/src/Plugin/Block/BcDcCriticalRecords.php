<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a overdue records block for the dashboard.
 *
 * @Block(
 *   id = "bc_dc_critical_records",
 *   admin_label = @Translation("Dashboard number of critical records"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user")
 *   }
 * )
 */
class BcDcCriticalRecords extends BlockBase implements ContainerFactoryPluginInterface {

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
      $plugin_defCriticalinition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    $build = [];

    $query = $nodeStorage
      ->getQuery()
      ->condition('status', 1)
      ->condition('type', 'data_set')
      ->condition('field_critical_information', TRUE)
      ->accessCheck(FALSE);

    $nids = $query->execute();
    $total_nodes = count($nids);

    $args = [
      '@count' => $total_nodes,
    ];

    $message = $this->formatPlural($total_nodes, '<p class="h2">@count</p><p class="word-wrap">Critical Dataset</p>', '<p class="h2">@count</p><p class="word-wrap">Critical Datasets</p>', $args);

    $build['message'] = [
      '#markup' => $message,
      '#prefix' => '<div class="card bcdc-dashboard-card"><div class="card-body">',
      '#suffix' => '</div></div>',
    ];

    return $build;
  }

}
