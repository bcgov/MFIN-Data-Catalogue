<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block that displays the dashboard actions.
 *
 * @Block(
 *   id = "bc_dc_dashboard_actions",
 *   admin_label = @Translation("Dashboard actions"),
 *   category = @Translation("BC Data Catalog"),
 * )
 */
class BcDcDashboardActions extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current_user service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected AccountProxyInterface $currentUser,
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
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $links = [];

    // Button to create a data_set.
    $url = Url::fromRoute('node.add', ['node_type' => 'data_set'], ['query' => ['display' => 'data_set_description']]);
    if ($url->access()) {
      $links[] = [
        '#title' => $this->t('Add new data set'),
        '#type' => 'link',
        '#url' => $url,
        '#attributes' => [
          'class' => [
            'button',
            'button--action',
            'button--primary',
          ],
        ],
      ];
    }

    // Link to saved searches.
    $url = Url::fromRoute('view.saved_searches.page', ['user' => $this->currentUser->id()]);
    if ($url->access()) {
      $links[] = [
        '#title' => $this->t('My saved searches'),
        '#type' => 'link',
        '#url' => $url,
        '#attributes' => [
          'class' => [
            'button',
          ],
        ],
      ];
    }

    // Return empty block when no links.
    if (!$links) {
      return [];
    }

    $build = [];

    // Wrapper element for actions.
    $build['actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'dc-dashboard-actions',
        ],
      ],
    ];

    foreach ($links as $link) {
      $build['actions'][] = $link;
    }

    return $build;
  }

}
