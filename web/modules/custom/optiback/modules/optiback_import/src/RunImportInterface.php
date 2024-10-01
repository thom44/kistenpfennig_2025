<?php

namespace Drupal\optiback_import;

/**
 * Run's the order export.
 */
interface RunImportInterface {

  /**
   * Starts the import.
   *
   * @param string $env
   *  The environment dev skips backups.
   * @param array $options
   *  The options product|invoice|tracking.
   *
   * @return string
   */
  public function run($env = 'prod', $options = []);

}
