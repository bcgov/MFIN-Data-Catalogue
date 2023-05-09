<?php

namespace Drupal\Tests\bc_dc\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests run on the current site instead of installing a fresh site.
 */
class BcDcExistingSiteTest extends ExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Cause tests to fail if an error is sent to Drupal logs.
    $this->failOnLoggedErrors();
  }

  /**
   * Return the result of ::randomString() without certain characters.
   *
   * These characters where causing problems in XPath queries.
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   The random string.
   */
  public function randomString($length = 8): string {
    $string = parent::randomString($length);
    $string = str_replace(['<', '{', '}'], '-', $string);
    return $string;
  }

  /**
   * Copy of BrowserTestBase::xpath().
   */
  protected function xpath($xpath, array $arguments = []) {
    $xpath = $this
      ->assertSession()
      ->buildXPathQuery($xpath, $arguments);
    return $this
      ->getSession()
      ->getPage()
      ->findAll('xpath', $xpath);
  }

  /**
   * Assert that the page URL matches a regex.
   *
   * @param string $regex
   *   The regex.
   *
   * @return bool
   *   TRUE of the URL matches, FALSE otherwise.
   */
  protected function assertUrlMatches(string $regex): bool {
    $url = $this->getUrl();
    $test = (bool) preg_match($regex, $url);
    $this->assertTrue($test, 'Page URL must match "' . $regex . '"; actual URL: ' . $url);
    return $test;
  }

  /**
   * Tests.
   */
  public function test(): void {
    // Test that front page returns HTTP 200.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
  }

}
