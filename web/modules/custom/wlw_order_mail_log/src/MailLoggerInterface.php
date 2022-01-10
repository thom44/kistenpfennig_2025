<?php

namespace Drupal\wlw_order_mail_log;

/**
 * Logs the order emails.
 */
interface MailLoggerInterface {

  /**
   * Saves the log data.
   *
   * @param array $log
   *   A array with the log entries.
   *   Keys:
   *   - order_id int The order id.
   *   - email_to string The receipt email address.
   *   - subject string The email subject.
   *   - email_key string The mailsystem key.
   *
   * @return int
   *   The last insert ID of the query, if one exists.|NULL
   */
  public function saveLogEntry($log);

  /**
   * Retrieves the log entries for an order.
   *
   * @param $order_id
   *   The order id.
   *
   * @return array
   *   The log entries.
   */
  public function getLogEntries($order_id);

}
