<?php

namespace Drupal\optiback_export;

use Drupal\custom_mail_ui\MailHelperInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\profile\Entity\Profile;
use Drupal\optiback\ObtibackConfigInterface;

class OptibackOrderExport {

  use StringTranslationTrait;

  /**
   * The email addresses.
   *
   * @var String $email
   */
  protected $email = [
    'from' => 'thom@licht.local',
    'to' => 'fritz@licht.local',
    'bcc' => 'thom@licht.local'
  ];

  /**
   * An instance of the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mail helper.
   *
   * @var \Drupal\custom_mail_ui\MailHelperInterface
   */
  protected $mailHelper;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger;
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    MailHelperInterface $mail_helper,
    LoggerChannelFactoryInterface $logger
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->mailHelper = $mail_helper;
    $this->logger = $logger;
  }

  /**
   * Export a CSV of data.
   *
   * @param string $logfile
   *  The logfile of the shellscript.
   */
  public function export($logfile = '') {

    // First we remove all csv export files, to export only new ones.
    array_map('unlink', glob(ObtibackConfigInterface::OPTIBACK_IN . '/*.csv'));

    $error = FALSE;

    $pos_data = [];

    // The csv AuKopf_ file header.
    $header = [
      'Bestell-Nr.',
      'Versand-Kosten',
      'Porto-Kosten',
      'Rechnungs-Rabatt in %',
      'Liefer-Adr. Anrede',
      'Liefer-Adr. Vorname',
      'Liefer-Adr. Nachname',
      'Liefer-Adr. Firma',
      'Liefer-Adr. Straße',
      'Liefer-Adr. Land',
      'Liefer-Adr. PLZ',
      'Liefer-Adr. Ort',
      'RG-Adr. Anrede',
      'RG -Adr. Vorname',
      'RG -Adr. Nachname',
      'RG -Adr. Firma',
      'RG -Adr. Straße',
      'RG -Adr. Land',
      'RG -Adr. PLZ',
      'RG -Adr. Ort',
    ];

    // The csv Position file header.
    $pos_header = [
      'Bestell-Nr.',
      'Artikel-Nr.',
      'Artikel-Menge',
      'Einzel-Preis inkl. Mwst',
      'Positions-Rabatt in %',
      'Positions-Wert nach Rabatt',
    ];

    $orders = $this->entityTypeManager
      ->getStorage('commerce_order')
      ->loadByProperties(
        [
          'state' => 'completed'
        ]
      );

    foreach ($orders as $order) {

      $export = $order->get('field_export')->getValue()[0]['value'];

      // Skip export if the order field_export is checked.
      if ($export) {
        continue;
      }

      $order_id = $order->id();

      // Build AuKopf data.
      $data[] = $this->buildOrderRow($order);

      $filename = 'AuKopf_' . $order_id . '.csv';
      // Save file AuKopf_{oid}.csv file in "in" directory.
      $result = $this->createCsvFile($header, $data, $filename);

      if (!$result) {
        $error = $this->t('The csv File :file could not be created.',[':file'=>$filename]);
      }
      foreach ($order->getItems() as $key => $order_item) {

        // Build Order Position data.
        $pos_data[] = $this->buildPositionRow($order_item);
      }

      $pos_filename = $order_id . '.csv';
      // Save file {oid}.csv file in "in" directory.
      $result = $this->createCsvFile($pos_header, $pos_data, $pos_filename);
      // Check field_export to prevent to export the order again.
      // The user can uncheck the field to cause update of the order.
      $order->set('field_export',1);
      $order->save();
    }


    // This is the "magic" part of the code.  Once the data is built, we can
    // return it as a response.
    #$response = new Response();
    // By setting these 2 header options, the browser will see the URL
    // used by this Controller to return a CSV file called "article-report.csv".
    #$response->headers->set('Content-Type', 'text/csv');
    #$response->headers->set('Content-Disposition', 'attachment; filename="AuKopf_' . $order_id . '.csv"');
    // This line physically adds the CSV data we created
    #$response->setContent($csv_data);

    if ($error == FALSE) {

      $this->logger->get('optiback_export')->error($error);

      // Email with attachment
      // @see optiback_mail() and mailsystem|swiftmail UI.
      $log_path = ObtibackConfigInterface::OPTIBACK_LOG . $logfile;

      // Gets the prepared file std class for attachement.
      $file = new \stdClass;
      $file->uri = $log_path;
      $file->filename = $logfile;
      $file->filemime = 'text/plain';

      $params = [
        'subject' => 'Drupal Optiback Export',
        'body' => 'Fehler beim Drupal Export<br>' . $error,
        'from' => ObtibackConfigInterface::EMAIL_FROM,
        'bcc' => ObtibackConfigInterface::EMAIL_BCC,
        'files' => [$file]
      ];

      $mail = $this->mailHelper->sendMail(
        'optiback_export',
        'optiback_export',
        ObtibackConfigInterface::EMAIL_TO,
        'de',
        $params
      );

      if ($mail) {
        $message = $this->t('The optiback export email was send to the site owner.');
        $this->logger->get('optiback_export')->error($message);
      } else {
        $message = $this->t('The optiback export email could not be send to the site owner.');
        $this->logger->get('optiback_export')->error($message);
      }

    }

    return $result;
  }

  /**
   * Fetches data and builds AuKopf CSV row.
   *
   * @param \Drupal\commerce_order\Entity\Order;
   *   Commerce Order.
   *
   * @return array
   *   Row data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function buildOrderRow(Order $order) {

   $adjustment_data = $this->getAdjustmentData($order);
   $discount = $this->getDiscountData($order);

   $profiles = $order->collectProfiles();
   if (isset($profiles['shipping'])) {
     $shipping = $this->getProfileData($profiles['shipping']);
   }
   if (isset($profiles['billing'])) {
     $billing = $this->getProfileData($profiles['billing']);
   }

   // The data key's not relevant. The order must fit to the header.
   $data = [
     'order_id'             => $order->id(),
     'shipping_cost'        => $adjustment_data['shipping_cost'], // float in EUR
     'porto_cost'           => 0, // Not available in Drupal Order.
     'order_discount'       => $discount['percentage'], // Float in percentage
     'shipping_salutation'  => $shipping['salutation'],
     'shipping_first_name'  => $shipping['first_name'],
     'shipping_last_name'   => $shipping['last_name'],
     'shipping_company'     => $shipping['company'],
     'shipping_street'      => $shipping['street'],
     'shipping_country'     => $shipping['country'],
     'shipping_post_code'   => $shipping['post_code'],
     'shipping_city'        => $shipping['city'],
     'billing_salutation'   => $billing['salutation'],
     'billing_first_name'   => $billing['first_name'],
     'billing_last_name'    => $billing['last_name'],
     'billing_company'      => $billing['company'],
     'billing_street'       => $billing['street'],
     'billing_country'      => $billing['country'],
     'billing_post_code'    => $billing['post_code'],
     'billing_city'         => $billing['city'],
   ];

   return $data;
 }

  /**
   * Fetches data and builds Position CSV row.
   *
   * @param \Drupal\commerce_order\Entity\Order;
   *   Commerce Order.
   *
   * @return array
   *   Row data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function buildPositionRow(OrderItem $order_item) {

    $data = [
      'order_id' => '',
      'sku'       => 0,
      'quantity'  => 0,
      'price'     => 0,
      'discount'  => 0,
      'total'     => 0,
    ];

    $quantity = $order_item->getQuantity();

    $product_variation = $order_item->getPurchasedEntity();

    // This is the gross (Brutto) price.
    $price = $product_variation->getPrice()->getNumber();

    // This is the gross (Brutto) price.
    $total = $order_item->getTotalPrice()->getNumber();

    // In Drupal sku is a string. In Optiback Artikel-Nr. is an integer value.
    // All sku fields will be filled only from Optiback import. So we are sure
    // it is an integer value.
    $sku = intval($product_variation->getSku());

    /*
     * Gets the product discount.

    $store_id = 1;
    $adjustment_types = array("promotion" => "promotion");
    $commercePriceCalc = \Drupal::service('commerce_order.price_calculator');
    $context = new Context(\Drupal::currentUser(),
      \Drupal::entityTypeManager()->getStorage('commerce_store')->load($store_id));

    $prices = $commercePriceCalc->calculate($product_variation, 1, $context, $adjustment_types);
    */

    // The data key's not relevant. The order must fit to the header.
    $data = [
      'order_id'  => $order_item->getOrderId(), // Optiback string
      'sku'       => $sku, // Optiback integer
      'quantity'  => number_format($quantity, 2, ',',''), // Optiback float
      'price'     => number_format($price, 3, ',',''), // Optiback float, 3 digit
      'discount'  => 0, // Optiback float
      'total' => number_format($total, 2, ',',''), // Optiback float, 2 digit
    ];

    return $data;
  }

  /**
   * Gets adjustment data for AuKopf_.
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   * @return int[]
   */
  public function getAdjustmentData(Order $order) {

    $data = [
      'shipping_cost' => 0,
    ];

    $adjustments = $order->getAdjustments();

    foreach ($adjustments as $adjustment) {
      if ($adjustment->getType() == 'shipping') {

        $amount = $adjustment->getAmount()->getNumber();
        $data['shipping_cost'] = number_format($amount, 2, ',','');
      }
    }

    return $data;
  }

  /**
   * Gets discount data for AuKopf_.
   *
   * @todo: Check promotion condition and operation.
   *   Order has only the coupon id attached. The calculation is not available
   *   the order object. It is calculated on crating invoice.
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   * @return int[]
   */
  public function getDiscountData(Order $order) {

    // Possible discount values.
    $data = [
      'percentage' => 0,
      'amount' => 0,
    ];

    $coupons = $order->get('coupons');

    foreach ($coupons as $coupon) {

      $coupon_obj = $coupon->get('entity')->getTarget()->getValue();

      $promotion = Promotion::load($coupon_obj->getPromotionId());
      $promotion_cfg = $promotion->getOffer()->getConfiguration();

      //$discount_amount = $discount_amount + $promotion_cfg['percentage'];
      $discount_percentage = $discount_percentage + $promotion_cfg['percentage'];
    }

    // Sum of discount values.
    //$data['amount'] = sprintf('%0.2f', $discount_amount * 100);
    $percentage = $discount_percentage * 100;

    $data['percentage'] = number_format($percentage, 2, ',','');

    return $data;
  }

  /**
   * Gets profile data for AuKopf_.
   *
   * @param \Drupal\profile\Entity\Profile $profile
   * @return int[]
   */
  public function getProfileData(Profile $profile) {

    // Makes sure that each key exists.
    $data = [
      'salutation' => '',
      'first_name' => '',
      'last_name' => '',
      'company' => '',
      'street' => '',
      'country' => '',
      'post_code' => '',
      'city' => '',
    ];

    if ($profile->hasField('field_salutation')) {
      $data['salutation'] = $profile->get('field_salutation')->getValue()[0]['value'];
    }
    if ($profile->hasField('field_first_name')) {
      $data['first_name'] = $profile->get('field_first_name')->getValue()[0]['value'];
    }
    if ($profile->hasField('field_last_name')) {
      $data['last_name'] = $profile->get('field_last_name')->getValue()[0]['value'];
    }

    $addresses = $profile->get('address')->getValue();

    foreach ($addresses as $address) {
      //$data['langcode'] = $address['langcode'];
      if (isset($address['country_code'])) {
        $data['country'] = $address['country_code'];
      }
      if (isset($address['locality'])) {
        $data['city'] = $address['locality'];
      }
      if (isset($address['postal_code'])) {
        $data['post_code'] = $address['postal_code'];
      }
      if (isset($address['address_line1'])) {
        $data['street'] = $address['address_line1'];
      }
      if (isset($address['address_line1'])) {
        $data['company'] = $address['address_line1'];
      }
    }

    return $data;
  }

  /**
   * Creates csv file.
   */
  public function createCsvFile($header, $data, $filename) {
    // Start using PHP's built in file handler functions to create a temporary file.
    $handle = fopen('php://temp', 'w+');

    // Add the header as the first line of the CSV.
    // We use tab delimiter \t.
    fputcsv($handle, $header, "\t");

    foreach ($data as $row) {
      // Add the data we exported to the next line of the CSV>
      fputcsv($handle, array_values($row), "\t");
    }

    // Reset where we are in the CSV.
    rewind($handle);

    // Retrieve the data from the file handler.
    $csv_data = stream_get_contents($handle);

    $result = file_put_contents(ObtibackConfigInterface::OPTIBACK_IN . '/' . $filename, $csv_data);
    // Close the file handler since we don't need it anymore.  We are not storing
    // this file anywhere in the filesystem.
    fclose($handle);

    return $result;
  }
}
