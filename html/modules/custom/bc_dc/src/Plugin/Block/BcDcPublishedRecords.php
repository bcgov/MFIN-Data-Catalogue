<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a published records block for the dashboard.
 *
 * @Block(
 *   id = "bc_dc_published_records",
 *   admin_label = @Translation("Dashboard number of published records"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user")
 *   }
 * )
 */
class BcDcPublishedRecords extends BlockBase implements ContainerFactoryPluginInterface {

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
    $user = $this->getContextValue('user');
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    $build = [];

    $query = $nodeStorage
      ->getQuery()
      ->condition('status', 1)
      ->condition('type', 'data_set')
      ->accessCheck(FALSE);
    $nids = $query->execute();
    $total_nodes = count($nids);

    $args = [
      '@count' => $total_nodes,
    ];

    $message = $this->formatPlural($total_nodes, '@count Published Metadata Record', '@count Published Metadata Records', $args);

    $build['message'] = [
      '#markup' => $message,
      '#prefix' => '<p class="p-2">',
      '#suffix' => '</p>',
    ];

    return $build;
  }

}
