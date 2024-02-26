<?php

namespace Drupal\bc_dc\Plugin\Action;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Plugin\Action\AssignOwnerNode;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Assigns node ownership offering only users with edit permission.
 *
 * @Action(
 *   id = "bc_dc_node_assign_owner_action",
 *   label = @Translation("Change content author"),
 *   type = "node"
 * )
 */
class BcDcAssignOwnerNode extends AssignOwnerNode {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $connection);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $user_storage = $this->entityTypeManager->getStorage('user');

    // Create array of uid => realname.
    $options = [];
    foreach ($this->getEditUsers() as $uid) {
      $user = $user_storage->load($uid);
      $options[$user->id()] = $user->getDisplayName();
    }
    asort($options, SORT_NATURAL | SORT_FLAG_CASE);

    $form['owner_uid'] = [
      '#type' => 'select',
      '#title' => $this->t('Username'),
      '#default_value' => $this->configuration['owner_uid'],
      '#options' => $options,
      '#description' => $this->t('The username of the user to which you would like to assign ownership.'),
    ];

    return $form;
  }

  /**
   * Return an array of UID of users who are allowed to edit data_set nodes.
   *
   * @return int[]
   *   The UIDs.
   */
  public function getEditUsers(): array {
    $user_storage = $this->entityTypeManager->getStorage('user');

    // Get all roles that have permission to edit.
    // Users with 'edit any data_set content' can edit anything. Users with
    // 'use  The form mode section_1 linked to  node entity( data_set )' can
    // edit if their organization matches (done via tac_lite).
    $rids = [];
    foreach (Role::loadMultiple() as $role) {
      if ($role->hasPermission('edit any data_set content') || $role->hasPermission('use  The form mode section_1 linked to  node entity( data_set )')) {
        $rids[$role->id()] = TRUE;
      }
    }
    $rids = array_keys($rids);

    // Query for all users in those roles.
    $query_edit_users = $user_storage
      ->getQuery()
      ->accessCheck(FALSE)
      // Only users with permission to edit.
      ->condition('roles', $rids, 'IN')
      // Exclude anonymous.
      ->condition('uid', 0, '>');

    return $query_edit_users->execute();
  }

}
