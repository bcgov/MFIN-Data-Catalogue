<?php

namespace Drupal\Tests\bc_dc\ExistingSite;

// This should not be needed because of autoloading, but without this, it cannot
// find BcbbExistingSiteBase.
require_once DRUPAL_ROOT . '/modules/contrib/bcbb/tests/src/ExistingSite/BcbbExistingSiteBase.php';

use Drupal\Tests\bcbb\ExistingSite\BcbbExistingSiteBase;

/**
 * Tests run on the current site instead of installing a fresh site.
 */
class BcDcExistingSiteTest extends BcbbExistingSiteBase {

  /**
   * Tests.
   */
  public function test(): void {
    // Test that front page returns HTTP 200.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);

    // Test that new users are assigned role data_catalogue_user.
    // There is also a functional test for this. That test sets its own config
    // instead of the config coming in by import.
    $this->createUser([], 'test_user');
    $account = user_load_by_name('test_user');
    $this->assertSession()->assert($account->hasRole('data_catalogue_user'), 'Test user should have role data_catalogue_user.');
  }

}
