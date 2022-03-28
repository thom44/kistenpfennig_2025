<?php

namespace Drupal\optiback;

/**
 * Provides an interface for optiback constants.
 */
interface ObtibackConfigInterface {

  /**
   * The db user.
   */
  const DB_USER = 'root';

  /**
   * The db user.
   */
  const DB_NAME = 'kd_kistenpfennig_d9';

  /**
   * The db user.
   */
  const DB_PWD = 'root';

  /**
   * The path to drush.
   */
  const DRUSH = 'drush';

  /**
   * The relative path to the optiback directory.
   */
  const OPTIBACK_DIR = '../../optiback/data/';

  /**
   * The relative path to the optiback directory.
   */
  const OPTIBACK_LOG = '../../optiback/logs/';

  /**
   * The relative path to the optiback directory.
   */
  const OPTIBACK_BAK = '../../optiback/backups/';

  /**
   * The relative path to the optiback directory.
   */
  const OPTIBACK_IN = ObtibackConfigInterface::OPTIBACK_DIR . '/in';

  /**
   * The relative path to the optiback directory.
   */
  const OPTIBACK_OUT = ObtibackConfigInterface::OPTIBACK_DIR . '/out';

  /**
   * The relative path to the optiback directory.
   */
  const OPTIBACK_INVOICE = ObtibackConfigInterface::OPTIBACK_OUT . '/invoice';

  /**
   * A prefix for moved pdf invoice filename.
   */
  const OPTIBACK_TRACKING = ObtibackConfigInterface::OPTIBACK_OUT . '/tracking-number';

  /**
   * The relative path to the private drupal invoice dir.
   */
  const DRUPAL_INVOICE = '../private/account-invoice';

  /**
   * The relative path to the private drupal invoice dir.
   */
  const DRUPAL_INVOICE_URI = 'private://account-invoice';

  /**
   * A prefix for moved pdf invoice filename.
   */
  const INVOICE_PREFIX = 'Rechnung_';

  /**
   * The email addresses.
   */
  const EMAIL_FROM = 'thom@licht.local';

  /**
   * The email addresses.
   */
  const EMAIL_TO = 'fritz@licht.local';

    /**
     * The email addresses.
     */
  const EMAIL_BCC = 'root@licht.local';

  /**
   * The Drupal tax_rate key.
   */
  const DRUPAL_TAX_DE_19 = 'default|cb66c91d-1ac6-4e64-9a9f-af14f1b8dcf1';

  /**
   * The Drupal tax_rate key.
   */
  const DRUPAL_TAX_DE_7 = 'default|9b0ae25c-0e66-41bd-b54a-6c6411869634';
}
