<?php

namespace Drupal\bcbb_book\Plugin\Block;

use Drupal\book\Plugin\Block\BookNavigationBlock;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;

/**
 * Provides a 'Book navigation' block showing only a configured book.
 */
#[Block(
  id: "bcbb_book_navigation",
  admin_label: new TranslatableMarkup("BC Base Build book navigation"),
  category: new TranslatableMarkup("Menus")
)]
class BcbbBookNavigationBlock extends BookNavigationBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();

    $form['book_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Book ID'),
      '#required' => TRUE,
      '#default_value' => $config['book_id'] ?? NULL,
      '#description' => $this->t('Show the navigation for this book.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['book_id'] = $form_state->getValue('book_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // Get the nid of the book to show the navigation for.
    $nid = $this->configuration['book_id'] ?? NULL;

    // Check that the node is published and the user has access.
    $nid = \Drupal::entityQuery('node')
      ->accessCheck(TRUE)
      ->condition('nid', $nid, '=')
      ->condition('status', NodeInterface::PUBLISHED)
      ->execute();
    $nid = reset($nid);
    $node = $nid ? $this->nodeStorage->load($nid) : NULL;

    // Check that the user has access and the node is a book.
    if ($node && $node->book) {
      $tree = $this->bookManager->bookTreeAllData($node->book['bid'], $node->book);
      // There should only be one element at the top level.
      $data = array_shift($tree);
      $below = $this->bookManager->bookTreeOutput($data['below']);
      if (!empty($below)) {
        return $below;
      }
    }

    // Default to empty block.
    return [];
  }

}
