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
    // Test that front page returns HTTP 200.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);

    // Robots control.
    $this->assertSession()->elementExists('xpath', '/head/meta[@name = "robots"][@content = "noindex, nofollow"]');

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

    // Search results download.
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "view-id-site_search")]//div[contains(@class, "row")]/div[contains(@class, "views-field-views-bulk-operations-bulk-form")]');
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "view-id-site_search")]//input[@id = "edit-select-all"][contains(@class, "vbo-select-all")]');
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "view-id-site_search")]//input[@value = "Generate csv from selected view results"]');

    // Facets exist on search page.
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "sidebar-region")]/div/section[contains(@class, "block-facet-blockprimary-responsibility-org")]/header/h2');
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "sidebar-region")]/div/section[contains(@class, "block-facet-blockseries")]/header/h2');
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "sidebar-region")]/div/section[contains(@class, "block-facet-blocksource-system")]/header/h2');
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "sidebar-region")]/div/section[contains(@class, "block-facet-blockused-in-products")]/header/h2');
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "sidebar-region")]/div/section[contains(@class, "block-facet-blockmetadata-type")]/header/h2');
    // Facet summary does not exist when no facets are selected.
    $container = $this->assertSession()->pageTextNotContains('Current search filters');
    $container = $this->assertSession()->elementNotExists('xpath', '//*[contains(@class, "block-facets-summary-blockfacets-summary")]/ul');
    // Use a facet.
    $this->click('section.block-facet-blockprimary-responsibility-org a');
    // Facet summary exists.
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-facets-summary-blockfacets-summary")]/h2[text() = "Current search filters"]');
    $container = $this->assertSession()->elementExists('xpath', '//div[contains(@class, "block-facets-summary-blockfacets-summary")]/ul');
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
      [*[text() = "Data custodian"]]
      [div/a[starts-with(@href, "/search/site?f%5B0%5D=author_id%3A")]]');
    $container = $this->assertSession()->elementExists('xpath', '//div
      [*[text() = "Office of primary responsibility"]]
      [div/a[starts-with(@href, "/search/site?f%5B0%5D=primary_responsibility_org%3A")]]');
    $container = $this->assertSession()->elementExists('xpath', '//div
      [*[text() = "Source system"]]
      [div/a[starts-with(@href, "/search/site?f%5B0%5D=source_system%3A")]]');
    $container = $this->assertSession()->elementExists('xpath', '//div
      [*[text() = "Series"]]
      [div/a[starts-with(@href, "/search/site?f%5B0%5D=series%3A")]]');
  }

}
