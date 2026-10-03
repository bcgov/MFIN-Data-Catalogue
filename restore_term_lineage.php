<?php

/**
 * @file
 * Phase 2 Migration: Restore lineage to recreated original field.
 *
 * Uses Direct Database Injection to bypass hook_entity_update,
 * Search API Solr crashes, and PHP memory limits (Exit Code 137).
 *
 * Execution: drush scr path/to/restore_term_lineage.php
 */

$database = \Drupal::database();

echo "--------------------------------------------------------\n";
echo "Starting Phase 2 (Direct DB Injection)...\n";
echo "--------------------------------------------------------\n";

// Grab all active data from the temporary field.
$query = $database->select('node__field_data_set_type_1', 'temp');
$query->fields('temp');
$temp_records = $query->execute()->fetchAll();

$total_records = count($temp_records);

if ($total_records === 0) {
  echo "No records found in temporary field. Exiting.\n";
  exit;
}

echo "Found $total_records field records to restore.\n\n";

// Safely clear the newly recreated destination tables to ensure a clean slate,
// making this script fully idempotent (safe to run multiple times).
$database->truncate('node__field_data_set_type')->execute();
$database->truncate('node_revision__field_data_set_type')->execute();

$processed = 0;

foreach ($temp_records as $record) {
  $insert_data = [
    'bundle' => $record->bundle,
    'deleted' => $record->deleted,
    'entity_id' => $record->entity_id,
    'revision_id' => $record->revision_id,
    'langcode' => $record->langcode,
    'delta' => $record->delta,
    // Map the value from the temp column name to the recreated original column name.
    'field_data_set_type_target_id' => $record->field_data_set_type_1_target_id,
  ];

  // Insert into both the active and revision tables for the original field.
  $database->insert('node__field_data_set_type')->fields($insert_data)->execute();
  $database->insert('node_revision__field_data_set_type')->fields($insert_data)->execute();

  $processed++;

  // Output progress less frequently since DB operations are lightning fast.
  if ($processed % 500 === 0) {
    echo "--> Restored $processed / $total_records records...\n";
  }
}

// Clear the entity cache so Drupal's memory reflects the raw database changes.
\Drupal::entityTypeManager()->getStorage('node')->resetCache();

echo "--------------------------------------------------------\n";
echo "Phase 2 Restoration Complete: $processed records moved.\n";
echo "--------------------------------------------------------\n";
