<?php

namespace Drupal\bc_dc\Plugin\Condition;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Provides a condition for whether the user may edit a node.
 *
 * @Condition(
 *   id = "bc_dc_build_edit_access",
 *   label = @Translation("Build page access"),
 *   context_definitions = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       required = TRUE,
 *       label = @Translation("Node")
 *     )
 *   }
 * )
 */
class BuildEditAccess extends ConditionPluginBase implements AccessInterface {

  /**
   * {@inheritdoc}
   *
   * This override is needed to provide default values. This is needed when this
   * class is used as an AccessInterface. The phpcs:ignore is to prevent a
   * warning about useless method overriding.
   */
  // phpcs:ignore
  public function __construct(array $configuration = [], $plugin_id = 'bc_dc_build_edit_access', $plugin_definition = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $entity = $this->getContextValue('node');

    $access = static::testAccess($entity);

    // Support negation.
    return $access xor $this->isNegated();
  }

  /**
   * {@inheritdoc}
   */
  public function summary(): TranslatableMarkup {
    return $this->t('User has access to edit the metadata record description.');
  }

  /**
   * Test for access on a given entity.
   *
   * @param Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity for which to test access.
   *
   * @return bool
   *   Whether access shold be granted.
   */
  protected static function testAccess(?EntityInterface $entity): bool {
    if (!$entity) {
      return FALSE;
    }

    if ($entity->bundle() !== 'data_set') {
      return FALSE;
    }

    // Give access based on whether the user has access to the edit path for
    // section_1.
    $route_parameters = [
      'node' => $entity->id(),
    ];
    $options = [
      'query' => [
        'display' => 'section_1',
      ],
    ];
    $access = Url::fromRoute('entity.node.edit_form', $route_parameters, $options)->access();

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public static function access(AccountInterface $account, RouteMatch $route_match): AccessResult {
    $entity = $route_match->getParameter('node');

    $access = static::testAccess($entity);

    return AccessResult::allowedIf($access);
  }

}
