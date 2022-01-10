<?php

namespace Drupal\wlw_order_mail_log;

use Drupal\Core\Database\Connection;

/**
 * Logs the order emails.
 */
class MailLogger implements MailLoggerInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * MailLogger constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritDoc}
   */
  public function saveLogEntry($log) {

    $result = $this->database->insert('wlw_order_mail_log')
      ->fields([
        'order_id' => $log['order_id'],
        'email_to' => $log['email_to'],
        'email_bcc' => $log['email_bcc'],
        'email_from' => $log['email_from'],
        'subject' => $log['subject'],
        'email_key' => $log['email_key'],
        'time' => time(),
      ])
      ->execute();

    return $result;
  }

  /**
   * {@inheritDoc}
   */
  public function getLogEntries($order_id) {

    $log = [];

    $result = $this->database->select('wlw_order_mail_log','woml')
      ->fields('woml',
        [
        'log_id',
        'order_id',
        'email_to',
        'email_bcc',
        'email_from',
        'subject',
        'email_key',
        'time'
      ])
      ->condition('woml.order_id', $order_id)
      ->orderBy('time')
      ->execute();

    $count=1;
    foreach ($result as $record) {

      $log[] = [
        $count,
        $record->order_id,
        date("d.m.y H:i",$record->time),
        $record->email_key,
        $record->subject,
        $record->email_to,
        $record->email_from,
        $record->email_bcc,
      ];
      $count++;
    }

    return $log;
  }
}
