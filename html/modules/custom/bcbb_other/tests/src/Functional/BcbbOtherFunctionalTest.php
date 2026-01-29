<?php

namespace Drupal\Tests\bcbb_other\Functional;

use Drupal\Tests\bcbb\Functional\BcbbBrowserTestBase;

/**
 * Functional tests.
 *
 * @group BcBb
 */
class BcbbOtherFunctionalTest extends BcbbBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bcbb_other',
  ];

  /**
   * Tests.
   */
  public function test(): void {
    // Homepage exists.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);

    // Place bcbb_text_block block with random content.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/structure/block/add/bcbb_text_block/bcbb_theme', ['query' => ['region' => 'content']]);
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'edit-settings-content-value' => 'Block content ' . $this->randomString(),
    ];
    $this->submitForm($edit, 'Save block');
    // Test that random content appears on the homepage.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains($edit['edit-settings-content-value']);
  }

}
