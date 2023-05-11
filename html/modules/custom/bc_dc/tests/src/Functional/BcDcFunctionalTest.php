<?php

namespace Drupal\Tests\bc_dc\Functional;

use Drupal\Core\Config\FileStorage;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Functional tests.
 *
 * @group BcDc
 */
class BcDcFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bc_dc',
  ];

  /**
   * Array of user objects keyed by user name.
   *
   * @var Drupal\user\Entity\User[]
   */
  protected $users = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    // Import config, like `drush config:import`.
    $config_path = DRUPAL_ROOT . '/../config/sync';
    $config_source = new FileStorage($config_path);
    \Drupal::service('config.installer')->installOptionalConfig($config_source);
  }

  /**
   * Create a user with roles.
   */
  protected function createTestUser(string $name, array $roles = []): User|false {
    // If the user exists, delete it before creation.
    $account = user_load_by_name($name);
    if ($account) {
      $account->delete();
    }

    // Create user with roles.
    $values = [
      'roles' => $roles,
    ];
    $account = $this->createUser([], $name, FALSE, $values);

    $this->users[$name] = $account;

    return $account;
  }

  /**
   * Tests.
   */
  public function test(): void {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);

    // Login as admin.
    $this->drupalLogin($this->rootUser);

    // Test that roles exist.
    $roles = [
      'data_administrator',
      'data_custodian',
      'data_catalogue_user',
    ];
    foreach ($roles as $role_id) {
      $role = Role::load($role_id);
      $this->assertSession()->assert($role instanceof Role, 'Role ' . $role_id . ' should exist.');
    }

    // Configure registration_role module.
    // @todo Remove this section and have the config come in from config import.
    $this->drupalGet('admin/people/registration-role');
    $edit = [
      'edit-role-to-select-data-catalogue-user' => TRUE,
      'edit-registration-mode-admin' => 'admin',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Create test users.
    $this->createTestUser('Test Data administrator', ['data_administrator']);
    $this->createTestUser('Test Data custodian', ['data_custodian']);
    $this->createTestUser('Test Data catalogue user', ['data_catalogue_user']);

    // Test that new users are assigned role data_catalogue_user.
    // This only works because of the registration_role module config done
    // above.
    foreach (array_keys($this->users) as $username) {
      $account = user_load_by_name($username);
      $this->assertSession()->assert($account->hasRole('data_catalogue_user'), 'Test user ' . $username . ' should have role data_catalogue_user.');
    }

    // Create a data_set node.
    $this->drupalGet('node/add/data_set', ['query' => ['display' => 'data_set_description']]);
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'edit-title-0-value' => 'Data set ' . $this->randomString(),
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Data set ' . $edit['edit-title-0-value'] . ' has been created');

    // Admin has access to build page.
    $this->drupalGet('node/1/build');
    $this->assertSession()->statusCodeEquals(200);

    // Anonymous has no access to build page.
    $this->drupalLogout();
    $this->drupalGet('node/1/build');
    $this->assertSession()->statusCodeEquals(403);
  }

}
