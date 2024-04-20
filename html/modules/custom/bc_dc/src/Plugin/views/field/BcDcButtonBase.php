<?php

namespace Drupal\bc_dc\Plugin\views\field;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Base class for buttons.
 */
abstract class BcDcButtonBase extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values): string|MarkupInterface {
    $node = $values->_entity;
    $user = \Drupal::currentUser();

    $buttonConfig = $this->buttonConfig();

    $options = [
      'attributes' => [
        'class' => ['btn'],
        'title' => $buttonConfig['text'] . ' ' . $node->getTitle(),
      ],
    ];
    $options['attributes']['class'][] = $buttonConfig['class'];

    if (!empty($buttonConfig['destination'])) {
      $options['query']['destination'] = '/user/' . $user->id() . '/manage';
    }

    $url = Url::fromRoute($buttonConfig['route'], ['node' => $node->id()], $options);

    // No button if no access.
    if (!$url->access()) {
      return '';
    }

    $text = $buttonConfig['text'];
    $link = Link::fromTextAndUrl($text, $url);
    $link_string = $link->toString();

    return $link_string;
  }

  /**
   * Return config for this button.
   *
   * @return string[]
   *   An array with keys 'text', 'class', and 'route'.
   */
  abstract protected function buttonConfig(): array;

}
