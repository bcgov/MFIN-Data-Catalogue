<?php

/**
 * @file
 * Phase 1 Migration: Extract lineage and populate temporary field.
 *
 * Workaround for CSHS locking "Save lineage" when data exists.
 * Reads the single term from field_data_set_type, calculates full lineage,
 * and saves the array to the temporary field_data_set_type_1.
 *
 * Execution: drush scr path/to/migrate_term_lineage.php
 */

$entity_type_manager = \Drupal::entityTypeManager();
$node_storage = $entity_type_manager->getStorage('node');
$term_storage = $entity_type_manager->getStorage('taxonomy_term');

echo "--------------------------------------------------------\n";
echo "Starting Phase 1: field_data_set_type -> field_data_set_type_1\n";
echo "--------------------------------------------------------\n";

// Find data_set nodes with legacy field data.
$query = $node_storage->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'data_set')
  ->exists('field_data_set_type')
  ->notExists('field_data_set_type_1');

$nids = $query->execute();
$total_nodes = count($nids);

if ($total_nodes === 0) {
  echo "No nodes found requiring migration. Exiting.\n";
  exit;
}

echo "Found $total_nodes nodes to process.\n\n";

$processed = 0;
$updated = 0;
$errors = 0;

// Cache term lineages to reduce DB queries.
$term_lineage_cache = [];

// Process in chunks of 50.
$chunks = array_chunk($nids, 50);

foreach ($chunks as $chunk) {
  try {
    $nodes = $node_storage->loadMultiple($chunk);

    foreach ($nodes as $nid => $node) {
      $processed++;

      // Get legacy term ID.
      $source_tid = $node->get('field_data_set_type')->target_id;

      if ($source_tid) {
        // Fetch and cache lineage if not already cached.
        if (!isset($term_lineage_cache[$source_tid])) {
          $lineage_terms = $term_storage->loadAllParents($source_tid);
          $term_lineage_cache[$source_tid] = array_keys($lineage_terms);
        }

        // Save full lineage array to temporary field.
        $node->set('field_data_set_type_1', $term_lineage_cache[$source_tid]);

        // Save node (triggers search reindexing).
        $node->save();
        $updated++;
      }
    }

    echo "--> Processed $processed / $total_nodes nodes... (Updated: $updated)\n";

    // Free memory.
    $node_storage->resetCache($chunk);

  } catch (\Exception $e) {
    echo "\n[ERROR] Failed processing chunk: " . $e->getMessage() . "\n";
    $errors++;
  }
}

echo "--------------------------------------------------------\n";
echo "Phase 1 Migration Complete!\n";
echo "--------------------------------------------------------\n";
echo "Total processed: $processed\n";
echo "Successfully updated: $updated\n";
echo "Errors: $errors\n";
echo "--------------------------------------------------------\n";
