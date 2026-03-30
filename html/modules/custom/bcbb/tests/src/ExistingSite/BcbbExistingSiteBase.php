<?php

namespace Drupal\Tests\bcbb\ExistingSite;

require_once __DIR__ . '/../BcbbTestingTrait.php';

use Drupal\Tests\bcbb\BcbbTestingTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Base class for tests that run on the current site.
 */
abstract class BcbbExistingSiteBase extends ExistingSiteBase {

  use BcbbTestingTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Cause tests to fail if an error is sent to Drupal logs.
    $this->failOnLoggedErrors();
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

}
