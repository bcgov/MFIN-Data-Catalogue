<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\flag\FlagService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Empty block exists to runs code on each page view.
 *
 * Due to caching, hooks like hook_ENTITY_TYPE_view() only run when a page is
 * built, not on every page view. This un-cached block gets built every time,
 * allowing running artibrary code each time. Empty block; nothing is displayed.
 *
 * If the node is bookmarked by the user, record that it has been viewed.
 *
 * @Block(
 *   id = "bc_dc_page_view_tracker",
 *   admin_label = @Translation("Page view tracker"),
 * )
 */
class PageViewTracker extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $currentRouteMatch
   *   The current_route_match service.
   * @param \Drupal\flag\FlagService $flagService
   *   The flag service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected RouteMatchInterface $currentRouteMatch,
    protected FlagService $flagService,
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
      $container->get('current_route_match'),
      $container->get('flag'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $entity = $this->currentRouteMatch->getParameter('node');

    // If the node exists and is bookmarked by the user, update last viewed.
    if ($entity) {
      // Load the bookmark for this user for this node.
      $bookmark_flag = $this->flagService->getFlagById('bookmark');
      $bookmark_flagging = $this->flagService->getFlagging($bookmark_flag, $entity);
      // Update field_last_viewed_date to now.
      if ($bookmark_flagging) {
        $bookmark_flagging->set('field_last_viewed_date', date('Y-m-d\TH:i:s'));
        $bookmark_flagging->save();
      }
    }

    // Return an empty block.
    $build = [
      // Disable cache so this will run every time.
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    return $build;
  }

}
