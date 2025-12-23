<?php

namespace Drupal\bc_dc\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\search_api\Entity\Index;

/**
 * Index Builder
 * 
 * Provides a way for users to run the search indexing
 * when they want to run it.
 */
class IndexBuilderController extends ControllerBase {

  public function runIndexing(Request $request) {
    $index = Index::load($index_id = 'default_index');

    if ($index) {
      $remaining_items = $index->getTrackerInstance()->getRemainingItemsCount();
      if (!$remaining_items) {
        $this->messenger()->addStatus($this->t('There were no items queued to add to the search index.'));
      }
      else {
        $indexed_count = $index->indexItems(50);

        $remaining_items = $index->getTrackerInstance()->getRemainingItemsCount();

        if ($remaining_items) {
          $this->messenger()->addStatus($this->t('Successfully indexed @count_items. There @are_still_x_items remaining in the queue.', [
            '@count_items' => \Drupal::translation()->formatPlural($indexed_count, '1 item', '@count items'),
            '@are_still_x_items' => \Drupal::translation()->formatPlural($remaining_items, 'is still 1 item', 'are still @count items'),
          ]));
        }
        else {
          $this->messenger()->addStatus($this->t('Successfully indexed @all_x_items_that_were in the queue.', [
            '@all_x_items_that_were' => \Drupal::translation()->formatPlural($indexed_count, 'the one item that was', 'all @count items that were'),
          ]));
        }
      }
    }
    else {
      throw new \Exception("Search index '$index_id' not found.");
    }

    // Send the user back to the page they were on, 
    // or to the homepage if they had come directly to us.
    $target = $request->headers->get('referer') ?: '/';
    return new RedirectResponse($target);
  }
}