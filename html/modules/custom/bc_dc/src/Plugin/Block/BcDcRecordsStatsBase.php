<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for statistics blocks for the dashboard.
 */
abstract class BcDcRecordsStatsBase extends BlockBase implements ContainerFactoryPluginInterface {

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
    $build = [];

    $query = $this->getQuery();

    $nids = $query->execute();
    $total_nodes = count($nids);

    $args = [
      '@count' => $total_nodes,
    ];

    $text = $this->getText();

    $message = $this->formatPlural($total_nodes, '<span class="dc-count">@count</span> <span>' . $text['singular'] . '</span>', '<span class="dc-count">@count</span> <span>' . $text['plural'] . '</span>', $args);

    $build['message'] = [
      '#markup' => $message,
      '#prefix' => '<div class="bcdc-dashboard-card"><p class="card-body">',
      '#suffix' => '</p></div>',
    ];

    return $build;
  }

  /**
   * Returns the query object to use for this block.
   *
   * @return Drupal\Core\Entity\Query\QueryInterface
   *   The query object.
   */
  abstract protected function getQuery(): QueryInterface;

  /**
   * Returns the strings to use in the output.
   *
   * @return string[]
   *   An array with keys 'singular' and 'plural' with the output strings.
   */
  abstract protected function getText(): array;

}
