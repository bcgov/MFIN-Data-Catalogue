<?php

namespace Drupal\Tests\bc_dc\ExistingSite;

// This should not be needed because of autoloading, but without this, it cannot
// find BcbbExistingSiteBase.
require_once DRUPAL_ROOT . '/modules/contrib/bcbb/tests/src/ExistingSite/BcbbExistingSiteBase.php';

use Drupal\search_api\Entity\Server as SearchApiServer;
use Drupal\Tests\bcbb\ExistingSite\BcbbExistingSiteBase;

/**
 * Tests run on the current site instead of installing a fresh site.
 */
class BcDcExistingSiteTest extends BcbbExistingSiteBase {

  /**
   * Tests.
   */
  public function test(): void {
    // Login page.
    $this->drupalGet('user/login');
    $this->assertSession()->statusCodeEquals(200);
    // No link to password reset page.
    $this->assertSession()->linkByHrefNotExists('user/password');

    // Login page with 'showcore' set.
    $this->drupalGet('user/login', ['query' => ['showcore' => '']]);
    $this->assertSession()->statusCodeEquals(200);
    // Link to password reset page.
    $this->assertSession()->linkByHrefExists('user/password');

    // Robots control.
    $this->assertSession()->elementExists('xpath', '/head/meta[@name = "robots"][@content = "noindex, nofollow"]');
    $this->drupalGet('robots.txt');
    $this->assertSession()->responseContains("User-agent: *\r\nDisallow: /");

    // Test that new users are assigned role data_catalogue_user.
    // There is also a functional test for this. That test sets its own config
    // instead of the config coming in by import.
    $test_user = $this->createUser([], 'test_user');
    $account = user_load_by_name('test_user');
    $this->assertSession()->assert($account->hasRole('data_catalogue_user'), 'Test user should have role data_catalogue_user.');

    // Test search page.
    $this->drupalGet('data-set');
    $this->assertSession()->statusCodeEquals(200);

    // Login button in the header.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "region-header")]/div[contains(@class, "block-user-login-block")]//input[@value = "Log in with IDIR"]');
    // No links in login block. By default, a password reset link appears.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "region-header")]/div[contains(@class, "block-user-login-block")]//a');

    // Header search block does not appear.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "block-bcbb-search-api-block")]');
    // Search block in main content area appears.
    $this->assertSession()->elementExists('xpath', '//main//input[@aria-label = "Search terms"]');

    // Components of search results.
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "view-id-site_search")]//div[contains(@class, "row")]/div[contains(@class, "search-result")]');
    $this->assertSession()->elementExists('xpath', 'h2', $container);
    $this->assertSession()->elementExists('xpath', 'h2/a[starts-with(@href, "/data-set/")]', $container);
    $this->assertSession()->elementExists('xpath', 'div[contains(@class, "views-field-search-api-excerpt")]', $container);
    // Check that no excerpts are longer than 255, adding 1 for the ellipsis.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "view-id-site_search")]//div[contains(@class, "row")]/div[contains(@class, "search-result")]/div[contains(@class, "views-field-search-api-excerpt")][string-length(normalize-space(text())) > 256]');
    // Check that no excerpts contain HTML tags.
    // @todo Tags are not being removed. Fix and then re-enable this test.
    // phpcs:ignore Drupal.Files.LineLength.TooLong
    // $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "view-id-site_search")]//div[contains(@class, "row")]/div[contains(@class, "search-result")]/div[contains(@class, "views-field-search-api-excerpt")]/*');
    // Check for an excerpt ends in ellipsis.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "view-id-site_search")]//div[contains(@class, "row")]/div[contains(@class, "search-result")]/div[contains(@class, "views-field-search-api-excerpt")][substring(normalize-space(text()), string-length(normalize-space(text()))) = "…"]');
    // List of metadata.
    $this->assertSession()->elementExists('xpath', 'ul[contains(@class, "bcbb-inline-list")]', $container);
    // There are exactly 3 items in the list.
    $this->assertSession()->elementExists('xpath', 'ul[contains(@class, "bcbb-inline-list")]/li[3]', $container);
    $this->assertSession()->elementNotExists('xpath', 'ul[contains(@class, "bcbb-inline-list")]/li[4]', $container);
    // Check classes of items.
    $this->assertSession()->elementExists('xpath', 'ul[contains(@class, "bcbb-inline-list")]/li[@class = "primary_responsibility_org"]', $container);
    $this->assertSession()->elementExists('xpath', 'ul[contains(@class, "bcbb-inline-list")]/li[@class = "data_set_editor"]', $container);
    $this->assertSession()->elementExists('xpath', 'ul[contains(@class, "bcbb-inline-list")]/li[@class = "modified_date"]', $container);

    // No search results download as anon.
    $container = $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "view-id-site_search")]//div[contains(@class, "row")]/div[contains(@class, "views-field-views-bulk-operations-bulk-form")]');

    // Login as regular user.
    $this->drupalGet('user/login', ['query' => ['showcore' => '']]);
    $this->submitForm([
      'name' => $test_user->getAccountName(),
      'pass' => $test_user->passRaw,
    ], 'Log in');
    // Return to search page.
    $this->drupalGet('data-set');
    $this->assertSession()->statusCodeEquals(200);

    // Search results download.
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "view-id-site_search")]//div[contains(@class, "row")]/div[contains(@class, "views-field-views-bulk-operations-bulk-form")]');
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "view-id-site_search")]//input[@id = "edit-select-all"][contains(@class, "vbo-select-all")]');
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "view-id-site_search")]//input[@value = "Generate csv from selected view results"]');

    // Facets exist on search page.
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "bcbb-search-sidebar")]//section[contains(@class, "block-facet-blockprimary-responsibility-org")]/h2');
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "bcbb-search-sidebar")]//section[contains(@class, "block-facet-blockseries")]/h2');
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "bcbb-search-sidebar")]//section[contains(@class, "block-facet-blocksource-system")]/h2');
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "bcbb-search-sidebar")]//section[contains(@class, "block-facet-blockmetadata-type")]/h2');
    // Facet summary does not exist when no facets are selected.
    $container = $this->assertSession()->pageTextNotContains('Current search filters');
    $container = $this->assertSession()->elementNotExists('xpath', '//*[contains(@class, "block-facets-summary-blockfacets-summary")]/ul');
    // Use a facet.
    $this->click('section.block-facet-blockprimary-responsibility-org a');
    // Facet summary exists.
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-facets-summary-blockfacets-summary")]/h2[text() = "Current search filters"]');
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-facets-summary-blockfacets-summary")]//ul');
    // Clear facets.
    $this->clickLink('Clear all');
    $container = $this->assertSession()->pageTextNotContains('Current search filters');

    // Check that search_api_solr is installed and Solr server is available.
    $moduleHandler = \Drupal::service('module_handler');
    $this->assertTrue($moduleHandler->moduleExists('search_api_solr'), 'Module search_api_solr should be installed.');
    $solr_backend = SearchApiServer::load('solr')->getBackend();
    $this->assertTrue($solr_backend->isAvailable(), 'Solr server should be available.');

    // Test that certain fields on data_set view pages link to a facet search
    // for that value.
    $this->drupalGet('data-set/test-set');
    $this->assertSession()->statusCodeEquals(200);
    $container = $this->assertSession()->elementExists('xpath', '//div
      [*[text() = "Data set editor"]]
      [div/a[starts-with(@href, "/data-set?f%5B0%5D=author_id%3A")]]');
    $container = $this->assertSession()->elementExists('xpath', '//div
      [*[text() = "Office of primary responsibility"]]
      [div/a[starts-with(@href, "/data-set?f%5B0%5D=primary_responsibility_org%3A")]]');
    $container = $this->assertSession()->elementExists('xpath', '//div
      [*[text() = "Source system"]]
      [div/a[starts-with(@href, "/data-set?f%5B0%5D=source_system%3A")]]');
    $container = $this->assertSession()->elementExists('xpath', '//div
      [*[text() = "Series"]]
      [div/a[starts-with(@href, "/data-set?f%5B0%5D=series%3A")]]');
    // Test breadcrumb on data_set view page.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "region-breadcrumb")]//li[@class = "breadcrumb-item"]//a[@href = "/data-set"]');
  }

}
