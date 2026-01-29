<?php

namespace Drupal\Tests\bcbb_search\Functional;

use Drupal\Tests\bcbb\Functional\BcbbBrowserTestBase;

/**
 * Functional tests.
 *
 * @group BcBb
 */
class BcbbSearchFunctionalTest extends BcbbBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bcbb_search',
  ];

  /**
   * Tests.
   */
  public function test(): void {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('search/site');
    $this->assertSession()->statusCodeEquals(200);

    // Login as admin.
    $this->drupalLogin($this->rootUser);

    // Create article node.
    $this->drupalGet('node/add/article');
    $edit_article = [
      'edit-title-0-value' => 'Article Title ' . $this->randomString(),
      'edit-body-0-value' => 'Article Body ' . $this->randomString(),
    ];
    $this->submitForm($edit_article, 'Save');

    // Create page node.
    $this->drupalGet('node/add/page');
    $edit_page = [
      'edit-title-0-value' => 'Page Title ' . $this->randomString(),
      'edit-body-0-value' => 'Page Body ' . $this->randomString(),
    ];
    $this->submitForm($edit_page, 'Save');

    $this->drupalGet('admin/content');

    $this->drupalGet('search/site');
    // Article.
    $this->assertSession()->elementTextEquals('xpath', '//a[@data-drupal-facet-item-value = "article"]', 'article 1');
    $this->assertSession()->pageTextContains($edit_article['edit-title-0-value']);
    $this->assertSession()->pageTextContains($edit_article['edit-body-0-value']);
    // Page.
    $this->assertSession()->elementTextEquals('xpath', '//a[@data-drupal-facet-item-value = "page"]', 'page 1');
    $this->assertSession()->pageTextContains($edit_page['edit-title-0-value']);
    $this->assertSession()->pageTextContains($edit_page['edit-body-0-value']);

    // Test facet search.
    $this->clickLink('article 1');
    $this->assertSession()->pageTextContains($edit_article['edit-title-0-value']);
    $this->assertSession()->pageTextNotContains($edit_page['edit-title-0-value']);

    // Test full text search with title.
    $edit = [
      'edit-search-api-fulltext' => 'Page Title',
    ];
    $this->submitForm($edit, 'Search');
    $this->assertSession()->pageTextNotContains($edit_article['edit-title-0-value']);
    $this->assertSession()->pageTextContains($edit_page['edit-title-0-value']);

    // Test full text search with body.
    $edit = [
      'edit-search-api-fulltext' => 'Article Body',
    ];
    $this->submitForm($edit, 'Search');
    $this->assertSession()->pageTextContains($edit_article['edit-title-0-value']);
    $this->assertSession()->pageTextNotContains($edit_page['edit-title-0-value']);

    // Place bcbb_search_api_block block with random search path.
    $this->drupalGet('admin/structure/block/add/bcbb_search_api_block/bcbb_theme', ['query' => ['region' => 'content']]);
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'edit-settings-search-search-url' => '/search/site/' . $this->randomMachineName(),
    ];
    $this->submitForm($edit, 'Save block');
    // Test that block appears on the homepage.
    $this->drupalGet('');
    $args = [
      ':search_path' => $edit['edit-settings-search-search-url'],
    ];
    $search_block_xpath = $this->assertSession()->buildXPathQuery('//section[contains(@class, "bcbb-search-api-form")]/form[@role = "search"]//input[@value = :search_path]', $args);
    $this->assertSession()->elementExists('xpath', $search_block_xpath);
    $advanced_search_xpath = $this->assertSession()->buildXPathQuery('//section[contains(@class, "bcbb-search-api-form")]/form[@role = "search"]/div/div/a[@href = :search_path][text() = "Advanced search"]', $args);
    $this->assertSession()->elementNotExists('xpath', $advanced_search_xpath);
    // Edit block to show advanced search link.
    $this->drupalGet('admin/structure/block/manage/bcbb_theme_bcbbsearchapiblock');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'edit-settings-search-show-advanced-link' => TRUE,
    ];
    $this->submitForm($edit, 'Save block');
    // Test that the block appears on the homepage and shows the search link.
    $this->drupalGet('');
    $this->assertSession()->elementExists('xpath', $search_block_xpath);
    $this->assertSession()->elementExists('xpath', $advanced_search_xpath);
  }

}
