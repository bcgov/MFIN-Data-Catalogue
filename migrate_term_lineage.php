<?php

/**
 * @file
 * Phase 1 Migration via Direct Database Injection (Root -> Child order).
 *
 * Execution: drush scr path/to/migrate_term_lineage.php
 */

$database = \Drupal::database();
$term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

echo "--------------------------------------------------------\n";
echo "Starting Phase 1 (Direct DB Injection)...\n";
echo "--------------------------------------------------------\n";

$query = $database->select('node__field_data_set_type', 'legacy');
$query->fields('legacy');
$legacy_records = $query->execute()->fetchAll();

if (empty($legacy_records)) {
  echo "No records found in legacy field. Exiting.\n";
  exit;
}

$total_records = count($legacy_records);
echo "Found $total_records node records to process.\n\n";

$processed = 0;
$term_lineage_cache = [];

// Wipe existing backwards data in temporary tables for a clean slate.
$database->truncate('node__field_data_set_type_1')->execute();
$database->truncate('node_revision__field_data_set_type_1')->execute();

foreach ($legacy_records as $record) {
  $source_tid = $record->field_data_set_type_target_id;

  // Calculate and cache lineage in top-down order (Root -> Child).
  if (!isset($term_lineage_cache[$source_tid])) {
    $lineage_terms = $term_storage->loadAllParents($source_tid);
    $term_lineage_cache[$source_tid] = array_reverse(array_keys($lineage_terms));
  }

  $lineage_tids = $term_lineage_cache[$source_tid];
  $delta = 0;

  foreach ($lineage_tids as $tid) {
    $insert_data = [
      'bundle' => $record->bundle,
      'deleted' => $record->deleted,
      'entity_id' => $record->entity_id,
      'revision_id' => $record->revision_id,
      'langcode' => $record->langcode,
      'delta' => $delta,
      'field_data_set_type_1_target_id' => $tid,
    ];

    $database->insert('node__field_data_set_type_1')->fields($insert_data)->execute();
    $database->insert('node_revision__field_data_set_type_1')->fields($insert_data)->execute();

    $delta++;
  }

  $processed++;

  if ($processed % 50 === 0) {
    echo "--> Processed $processed / $total_records records...\n";
  }
}

// Clear node entity cache so Drupal immediately sees the new order.
\Drupal::entityTypeManager()->getStorage('node')->resetCache();

echo "--------------------------------------------------------\n";
echo "Phase 1 Complete: $processed nodes updated in Root -> Child order.\n";
echo "--------------------------------------------------------\n";
