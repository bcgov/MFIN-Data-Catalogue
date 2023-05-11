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
  }

}
