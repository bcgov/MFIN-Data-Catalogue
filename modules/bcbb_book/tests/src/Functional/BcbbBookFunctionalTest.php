<?php

namespace Drupal\Tests\bcbb_book\Functional;

use Drupal\Tests\bcbb\Functional\BcbbBrowserTestBase;

/**
 * Functional tests.
 *
 * @group BcBb
 */
class BcbbBookFunctionalTest extends BcbbBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bcbb_book',
    // For no known reason, without devel installed in tests, details#edit-book
    // will appear when it should not, causing the tests for this to fail.
    'devel',
  ];

  /**
   * Tests.
   */
  public function test(): void {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);

    // Test book module.
    //
    // Page for creating a book.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('node/add/book');
    $this->assertSession()->statusCodeEquals(200);
    // "Book outline" edit section appears.
    $this->assertSession()->elementExists('xpath', '//details[@id = "edit-book"]');
    // Create a Book.
    $edit_book = [
      'edit-title-0-value' => 'Test Book ' . $this->randomString(),
      'edit-book-bid' => 'new',
    ];
    $this->submitForm($edit_book, 'Save');
    $text = $this->assertSession()->elementExists('xpath', '//h1')->getText();
    $this->assertStringContainsString($edit_book['edit-title-0-value'], $text);
    $book_url = $this->getUrl();
    // "Outline" tab appears.
    $this->assertSession()->elementExists('xpath', '//nav[@aria-label = "Tabs"]//a[@class = "nav-link"][normalize-space(text()) = "Outline"]');
    // Book edit page.
    $this->drupalGet($book_url . '/edit');
    // "Outline" tab appears.
    $this->assertSession()->elementExists('xpath', '//nav[@aria-labelledby = "primary-tabs-title"]//a[normalize-space(text()) = "Outline"]');
    // "Book outline" edit section appears.
    $this->assertSession()->elementExists('xpath', '//details[@id = "edit-book"]');
    // Create child page.
    $this->drupalGet($book_url);
    $this->clickLink('Add child page');
    $this->assertSession()->statusCodeEquals(200);
    $edit_child = [
      'edit-title-0-value' => 'Test Book Child Page ' . $this->randomString(),
      'edit-body-0-summary' => 'Test Book Child Summary ' . $this->randomString(),
    ];
    $this->submitForm($edit_child, 'Save');
    $text = $this->assertSession()->elementExists('xpath', '//h1')->getText();
    $this->assertStringContainsString($edit_child['edit-title-0-value'], $text);
    $child_url = $this->getUrl();
    // Create grandchild.
    $this->clickLink('Add child page');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'edit-title-0-value' => 'Test Book Grandchild Page ' . $this->randomString(),
    ];
    $this->submitForm($edit, 'Save');
    $text = $this->assertSession()->elementExists('xpath', '//h1')->getText();
    $this->assertStringContainsString($edit['edit-title-0-value'], $text);
    // Book title appears in breadcrumbs.
    $args = [
      ':title' => $edit_book['edit-title-0-value'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//nav/ol[@class = "breadcrumb"]/li/a[text() = :title]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Main book page.
    // Summary appears in list of child pages.
    $this->drupalGet($book_url);
    $args = [
      ':summary' => $edit_child['edit-body-0-summary'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//nav[@class = "book-navigation"]/ul/li/div[contains(@class, "summary")][text() = :summary]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Book traversal links.
    $args = [
      ':title' => $edit_child['edit-title-0-value'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//nav[@class = "book-navigation"]/ul[@aria-label = "Document navigation"]/li/a[@title = "Go to next page"]/*[contains(text(), :title)]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
    // Default "Book traversal links" header is not present.
    $this->assertSession()->pageTextNotContains('Book traversal links');
    // Printer-friendly version.
    $this->assertSession()->elementExists('xpath', '//div[@class = "node__links"]/ul/li/a[contains(text(), "Printer-friendly version")]');
    // Child page.
    $this->drupalGet($child_url);
    // Child page does not have list of child pages.
    $this->assertSession()->elementNotExists('xpath', '//nav[@class = "book-navigation"]/ul[not(@aria-label)]');

    // Book tabs and controls do not appear on non-book content types.
    $this->drupalGet('admin/structure/book/settings');
    $this->drupalGet('node/add/page');
    // "Book outline" edit section does not appear.
    $this->assertSession()->elementNotExists('xpath', '//details[@id = "edit-book"]');
    $edit = [
      'edit-title-0-value' => 'Test Basic Page ' . $this->randomString(),
    ];
    $this->submitForm($edit, 'Save');
    $page_url = $this->getUrl();
    // "Outline" tab does not appear.
    $this->assertSession()->elementNotExists('xpath', '//nav[@aria-label = "Tabs"]//a[@class = "nav-link"][normalize-space(text()) = "Outline"]');
    // Edit page.
    $this->drupalGet($page_url . '/edit');
    // "Outline" tab does not appear.
    $this->assertSession()->elementNotExists('xpath', '//nav[@aria-labelledby = "primary-tabs-title"]//a[normalize-space(text()) = "Outline"]');
    // "Book outline" edit section does not appear.
    $this->assertSession()->elementNotExists('xpath', '//details[@id = "edit-book"]');

    // Test bcbb_book_navigation block.
    //
    // Place block.
    $this->drupalGet('admin/structure/block/add/bcbb_book_navigation/bcbb_theme', ['query' => ['region' => 'sidebar_first']]);
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'edit-settings-book-id' => 1,
    ];
    $this->submitForm($edit, 'Save block');
    $this->assertSession()->statusCodeEquals(200);
    // Test that book navigation appears on the homepage.
    $this->drupalGet('');
    $args = [
      ':title' => $edit_child['edit-title-0-value'],
    ];
    $xpath = $this->assertSession()->buildXPathQuery('//*[@id = "block-bcbb-theme-bcbasebuildbooknavigation"]/ul/li/a[@href = "/node/2"][text() = :title]', $args);
    $this->assertSession()->elementExists('xpath', $xpath);
  }

}
