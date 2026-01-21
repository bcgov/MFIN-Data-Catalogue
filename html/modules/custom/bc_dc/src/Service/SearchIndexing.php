<?php

namespace Drupal\bc_dc\Service;

use Drupal\search_api\Entity\Index;
use Drupal\node\NodeInterface;
use Drupal\search_api\Utility\ContentEntityHelper;

/**
 * Service for adding content changes to the search index.
 */
class SearchIndexing  {
  public function reindexNode(NodeInterface $node) {
    $index = Index::load($index_id = 'default_index');
    
    if (!$index->status()) throw new \Exception("Can't find index '$index_id'.");

    $item_id = sprintf('entity:node/%d:%s', $node->id(), $node->language()->getId());
    if ($item = $index->loadItem($item_id)) {
      // Force immediate indexing.
      $index->indexSpecificItems([$item_id => $item]);
    }
  }
}
