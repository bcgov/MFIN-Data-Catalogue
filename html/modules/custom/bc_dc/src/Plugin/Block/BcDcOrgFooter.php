<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the "Review needed" message block.
 *
 * @Block(
 *   id = "bc_dc_org_footer",
 *   admin_label = @Translation("Organization footer"),
 * )
 */
class BcDcOrgFooter extends BlockBase implements ContainerFactoryPluginInterface {

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected AccountProxyInterface $currentUser,
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
      $container->get('current_user'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $account = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

    // Get the user's organization.
    $org = NULL;
    if ($account && $account->hasField('field_organization')) {
      $org_id = $account->get('field_organization')->first()?->getValue();
      $org_id = $org_id['target_id'] ?? NULL;
      if ($org_id) {
        $org = $this->entityTypeManager->getStorage('taxonomy_term')->load($org_id);
      }
    }

    // Cache block for each user.
    $build = [
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];

    // Early return empty block if user has no org.
    if (!$org) {
      return $build;
    }

    // Set the block title to the term title.
    $build['#title'] = $org->getName();

    // Block contents are the bc_dc_org_footer display mode of the term.
    $view_builder = $this->entityTypeManager->getViewBuilder('taxonomy_term');
    $build += $view_builder->view($org, 'bc_dc_org_footer');

    return $build;
  }

}
