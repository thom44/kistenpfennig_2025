<?php

namespace Drupal\optiback_export;

/**
 * Run's the order export.
 */
interface RunExportInterface {

  /**
   * Starts the export.
   *
   * @param (optional) string $env
   *  The environment dev skips backups.
   *
   * @return boolish TRUE|FALSE
   */
  public function run($env = 'prod');

}
