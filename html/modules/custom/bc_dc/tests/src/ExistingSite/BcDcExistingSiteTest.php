<?php

namespace Drupal\Tests\bc_dc\ExistingSite;

// This should not be needed because of autoloading, but without this, it cannot
// find BcbbExistingSiteBase.
require_once DRUPAL_ROOT . '/modules/contrib/bcbb/tests/src/ExistingSite/BcbbExistingSiteBase.php';

use Drupal\Tests\bcbb\ExistingSite\BcbbExistingSiteBase;
use Drupal\user\Entity\User;

/**
 * Tests run on the current site instead of installing a fresh site.
 */
class BcDcExistingSiteTest extends BcbbExistingSiteBase {

  /**
   * Array of user objects keyed by user name.
   *
   * @var Drupal\user\Entity\User[]
   */
  protected $users = [];

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
    // Test that front page returns HTTP 200.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);

    $this->createTestUser('Test Data administrator', ['data_administrator']);
    $this->createTestUser('Test Data custodian', ['data_custodian']);
    $this->createTestUser('Test Data catalogue user', ['data_catalogue_user']);
  }

}
