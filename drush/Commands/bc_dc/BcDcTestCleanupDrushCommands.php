<?php

namespace Drush\Commands\bc_dc;

use Drush\Commands\DrushCommands;
use Drush\Sql\SqlBase;

/**
 * Drush command file.
 */
class BcDcTestCleanupDrushCommands extends DrushCommands {

  /**
   * Drush command to delete tables left over from running tests.
   *
   * @param string $db_url
   *   The URL of the database, for example, "pgsql://user:pass@localhost/db".
   *
   * @command bcdc:clean-test-tables
   *
   * @usage bcdc:clean-test-tables pgsql://user:pass@localhost/db
   *
   * @todo It ought to be possible for Drush to get the DB URL from the site
   * config instead of requiring it as param.
   */
  public function deleteTestTables(string $db_url): void {
    $msg = dt('Deleting tables left over from running tests...');
    $this->output()->writeln($msg);

    $database = SqlBase::create(['db-url' => $db_url]);

    $query = 'DO
    $do$
    BEGIN
      EXECUTE (
        SELECT \'DROP TABLE \' || string_agg(format(\'%I.%I\', schemaname, tablename), \', \')
        FROM (
          SELECT *
          FROM pg_catalog.pg_tables t
          WHERE schemaname NOT LIKE \'pg\_%\' -- Exclude system schemas.
            AND tablename LIKE \'test%\' -- Prefix of tables to remove.
          LIMIT 500 -- Limit to avoid running out of memory.
        ) sub
      );
    END
    $do$;';

    $success = $database->query($query);

    if ($success) {
      $msg = dt('Done.');
      $this->output()->writeln($msg);
    }
    else {
      throw new \Exception(dt('Query failed.'));
    }
  }

}
