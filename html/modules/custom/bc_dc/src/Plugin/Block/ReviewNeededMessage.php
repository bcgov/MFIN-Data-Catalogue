<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\bc_dc\Trait\ReviewReminderTrait;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the "Review needed" message block.
 *
 * @Block(
 *   id = "bc_dc_review_needed_message",
 *   admin_label = @Translation("Review needed message"),
 * )
 */
class ReviewNeededMessage extends BlockBase implements ContainerFactoryPluginInterface {

  use ReviewReminderTrait;

  const REVIEW_NEEDED = 1;
  const REVIEW_OVERDUE = 2;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $currentRouteMatch
   *   The current_route_match service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ConfigFactoryInterface $configFactory,
    protected RouteMatchInterface $currentRouteMatch,
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
      $container->get('config.factory'),
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $node = $this->currentRouteMatch->getParameter('node');

    // Get variables for the correct version of the message.
    $review_needed = match(static::dataSetReviewNeeded($node)) {
      static::REVIEW_OVERDUE => [
        '#classes' => 'alert alert-error alert-danger dc-review',
        '#content' => $this->configFactory->get('bc_dc.settings')->get('review_overdue_message'),
      ],
      static::REVIEW_NEEDED => [
        '#classes' => 'alert alert-warning dc-review',
        '#content' => $this->configFactory->get('bc_dc.settings')->get('review_needed_message'),
      ],
      default => NULL,
    };

    // Display nothing if no review is needed.
    $build = [
      // Cache block for each page.
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => ['node:' . $node->id()],
      ],
    ];

    // Add block contents if review is needed.
    if ($review_needed) {
      $build['#theme'] = 'bc_dc_row_mb3';
      $build += $review_needed;
    }

    return $build;
  }

}
