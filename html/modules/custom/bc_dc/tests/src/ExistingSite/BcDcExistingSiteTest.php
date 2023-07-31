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

    // Test search page.
    $this->drupalGet('search/site');
    $this->assertSession()->statusCodeEquals(200);
    // Components of search results.
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "view-id-site_search")]//div[contains(@class, "row")]/div[contains(@class, "search-result")]');
    $this->assertSession()->elementExists('xpath', 'h2', $container);
    $this->assertSession()->elementExists('xpath', 'h2/a[starts-with(@href, "/data-set/")]', $container);
    $this->assertSession()->elementExists('xpath', 'div[contains(@class, "views-field-search-api-excerpt")]', $container);
    // Check that no excerpts are longer than 255, adding 1 for the ellipsis.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "view-id-site_search")]//div[contains(@class, "row")]/div[contains(@class, "search-result")]/div[contains(@class, "views-field-search-api-excerpt")][string-length(normalize-space(text())) > 256]');
    // Check that no excerpts contain HTML tags.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "view-id-site_search")]//div[contains(@class, "row")]/div[contains(@class, "search-result")]/div[contains(@class, "views-field-search-api-excerpt")]/*');
    // Check for an excerpt ends in ellipsis.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "view-id-site_search")]//div[contains(@class, "row")]/div[contains(@class, "search-result")]/div[contains(@class, "views-field-search-api-excerpt")][substring(normalize-space(text()), string-length(normalize-space(text()))) = "…"]');
    // List of metadata.
    $this->assertSession()->elementExists('xpath', 'ul[contains(@class, "bcbb-inline-list")]', $container);
    // There are exactly 3 items in the list.
    $this->assertSession()->elementExists('xpath', 'ul[contains(@class, "bcbb-inline-list")]/li[3]', $container);
    $this->assertSession()->elementNotExists('xpath', 'ul[contains(@class, "bcbb-inline-list")]/li[4]', $container);
    // Check classes of items.
    $this->assertSession()->elementExists('xpath', 'ul[contains(@class, "bcbb-inline-list")]/li[@class = "primary_responsibility_org"]', $container);
    $this->assertSession()->elementExists('xpath', 'ul[contains(@class, "bcbb-inline-list")]/li[@class = "data_custodian"]', $container);
    $this->assertSession()->elementExists('xpath', 'ul[contains(@class, "bcbb-inline-list")]/li[@class = "modified_date"]', $container);
  }

}
