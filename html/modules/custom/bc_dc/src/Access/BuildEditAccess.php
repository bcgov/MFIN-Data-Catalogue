<?php

namespace Drupal\bc_dc\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Access check for whether the user may edit a data_set node.
 */
class BuildEditAccess implements AccessInterface {

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
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatchInterface object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function access(AccountInterface $account, RouteMatchInterface $route_match): AccessResultInterface {
    $entity = $route_match->getParameter('node');

    $access = static::testAccess($entity);

    return AccessResult::allowedIf($access);
  }

  /**
   * A custom access check for route bc_dc.data_set_edit_add_columns.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatchInterface object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function accessAddColumns(AccountInterface $account, RouteMatchInterface $route_match): AccessResultInterface {
    $node = $route_match->getParameter('node');

    // If this node has field_columns, access is the same as ::access().
    if ($node && bc_dc_data_set_has_field($node, 'field_columns')) {
      return static::access($account, $route_match);
    }
    // Otherwise, no access for anyone.
    else {
      return AccessResult::forbidden();
    }
  }

}
