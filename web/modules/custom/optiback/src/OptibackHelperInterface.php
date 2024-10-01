<?php

namespace Drupal\optiback;

/**
 * Provides an interface for optiback constants.
 */
interface OptibackHelperInterface {

  /**
   * @param $env
   * @return mixed
   */
  public function dbBackup($env = 'prod');

  /**
   * @param $cmd
   * @param $message
   * @return mixed
   */
  public function shellExecWithError($cmd, $message);

  /**
   * @param $directory
   * @param $name
   * @return mixed
   */
  public function dirBackup($directory, $name = '');

  /**
   * @return mixed
   */
  public function backupCleanup();

  /**
   * @param $filename
   * @param $delimiter
   * @return mixed
   */
  public function csvToArray($filename, $delimiter);
}
