<?php

namespace Drupal\optiback\Commands;

use Drupal\backup_migrate\Core\Translation\TranslatableTrait;
use Drupal\optiback\ObtibackConfigInterface;
use Drupal\optiback\OptibackLoggerInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 * @see https://www.axelerant.com/blog/how-to-write-custom-drush-9-commands-for-drupal-8
 */
class DrushSendLog extends DrushCommands {

  use TranslatableTrait;

  /**
   * The optiback logger service.
   *
   * @var \Drupal\optiback\OptibackLoggerInterface
   */
  protected $optibackLogger;

  public function __construct(OptibackLoggerInterface $optiback_logger) {
    $this->optibackLogger = $optiback_logger;
  }

  /**
   * Sends an email with the shell logfile from the script.
   *
   * @param string $logfile
   *   The logfile path.
   *
   * @command optiback:send_log
   * @aliases send_log
   * @usage send_log logfile
   *   Run import on local mashine
   */
  public function run($logfile) {

    $log_path = ObtibackConfigInterface::OPTIBACK_LOG . $logfile;

    // Gets the prepared file std class for attachement.
    $file = new \stdClass;
    $file->uri = $log_path;
    $file->filename = $logfile;
    $file->filemime = 'text/plain';

    $params = [
      'subject' => 'Drupal Optiback Logfile',
      'body' => 'Im Anhang ist das Optiback logfile.',
      'files' => [$file]
    ];

    $mail = $this->optibackLogger->sendMail($params);

    if (!$mail) {
      $message = $this->t('The optiback log email could not be send to the site owner.');
      #$this->logger->get('optiback_export')->error($message);
    }
  }

}
