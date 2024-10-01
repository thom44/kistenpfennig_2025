<?php

namespace Drupal\optiback_export;

/**
 * Run's the order export.
 */
interface RunExportInterface {

  /**
   * @param $env
   * @return mixed
   */
  public function run($env = 'prod');

}
