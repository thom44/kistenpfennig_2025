<?php

namespace Drupal\optiback;

/**
 * Provides an interface for optiback constants.
 */
interface OptibackLoggerInterface {

  /**
   * @param $message string
   *   The log message.
   * @param $level string
   *   The level warning|error
   * @return mixed
   */
  public function addLog($message, $level);

  /**
   * @param $params
   * @return mixed
   */
  public function sendMail($params);

}
