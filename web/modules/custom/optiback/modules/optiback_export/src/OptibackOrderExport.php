<?php

namespace Drupal\optiback_export;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\custom_mail_ui\MailHelperInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\profile\Entity\Profile;
use Drupal\optiback\ObtibackConfigInterface;
use Drupal\optiback\OptibackLoggerInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use function PHPUnit\Framework\isInstanceOf;

class OptibackOrderExport {

  use StringTranslationTrait;

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
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The optiback logger service.
   *
   * @var \Drupal\optiback\OptibackLoggerInterface
   */
  protected $optibackLogger;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    MailHelperInterface $mail_helper,
    LoggerChannelFactoryInterface $logger,
    OptibackLoggerInterface $optiback_logger
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->mailHelper = $mail_helper;
    $this->logger = $logger;
    $this->optibackLogger = $optiback_logger;
  }

  /**
   * Export a CSV of data.
   */
  public function export() {

    $result = NULL;
    $error = FALSE;
    $pos_data = [];

    // First we remove all csv export files, to export only new ones.
    // This is done by optiback.
    //array_map('unlink', glob(ObtibackConfigInterface::OPTIBACK_IN . '/*.csv'));

    // The csv AuKopf_ file header.
    $header = [
      'Bestell-Nr.',
      'Versand-Kosten',
      'Porto-Kosten',
      'Rechnungs-Rabatt in %',
      'E-Mail',
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
      'Zahlart',
      'Zahlungs ID.'
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

    $states = [
      'paid',
      'fulfillment',
      'completed'
    ];

    $orders = $this->entityTypeManager
      ->getStorage('commerce_order')
      ->loadByProperties(
        [
          'state' => $states
        ]
      );

    foreach ($orders as $order) {

      if (!$order instanceof OrderInterface) {
        continue;
      }

      $data = [];
      $pos_data = [];
      $result = [];

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

      $pos_filename = 'AuPos_' . $order_id . '.csv';
      // Save file {oid}.csv file in "in" directory.
      $result = $this->createCsvFile($pos_header, $pos_data, $pos_filename);
      // Check field_export to prevent to export the order again.
      // The user can uncheck the field to cause update of the order.
      $order->set('field_export',1);
      $order->save();
    }

    if ($error) {

      $this->logger->get('optiback_export')->error($error);

      $this->optibackLogger->addLog($error, 'error');

      $params = [
        'subject' => 'Drupal Optiback Export',
        'body' => 'Fehler beim Drupal Export<br>',
      ];

      $mail = $this->optibackLogger->sendMail($params);

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
   * {@inheritdoc}
   */
  private function buildOrderRow(Order $order) {

    $data = [];
    $shipping = NULL;
    $billing = NULL;
    $payment_gateway_label = '';
    $payment_id = NULL;

    $adjustment_data = $this->getAdjustmentData($order);

    if (!$order->get('payment_gateway')->isEmpty()) {

      $payment_gateway_item = $order->get('payment_gateway')->first();

      // Check if there is a valid target_id (entity reference).
      if (!empty($payment_gateway_item->getValue()['target_id'])) {
        // Load the payment gateway entity using the entityTypeManager.
        $payment_gateway = $this->entityTypeManager->getStorage('commerce_payment_gateway')
          ->load($payment_gateway_item->getValue()['target_id']);

        if ($payment_gateway instanceof PaymentGatewayInterface) {
          $payment_gateway_label = $payment_gateway->label();
          $payment_id = $payment_gateway->id();
        }

        // Retrieve the payment remote id.
        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        $payments = $payment_storage->loadByProperties(['order_id' => $order->id()]);
        $payment = reset($payments);

        if ($payment instanceof PaymentInterface) {
          $payment_id = $payment->get('remote_id')->value;
        }

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
          'order_discount'       => 0, // $discount['percentage'], // Float in percentage
          'E-Mail'               => $order->getEmail(),
          'shipping_salutation'  => $this->cleanString($shipping['salutation']),
          'shipping_first_name'  => $this->cleanString($shipping['first_name']),
          'shipping_last_name'   => $this->cleanString($shipping['last_name']),
          'shipping_company'     => $this->cleanString($shipping['company']),
          'shipping_street'      => $this->cleanString($shipping['street']),
          'shipping_country'     => $this->cleanString($shipping['country']),
          'shipping_post_code'   => $this->cleanString($shipping['post_code']),
          'shipping_city'        => $this->cleanString($shipping['city']),
          'billing_salutation'   => $this->cleanString($billing['salutation']),
          'billing_first_name'   => $this->cleanString($billing['first_name']),
          'billing_last_name'    => $this->cleanString($billing['last_name']),
          'billing_company'      => $this->cleanString($billing['company']),
          'billing_street'       => $this->cleanString($billing['street']),
          'billing_country'      => $this->cleanString($billing['country']),
          'billing_post_code'    => $this->cleanString($billing['post_code']),
          'billing_city'         => $this->cleanString($billing['city']),
          'payment_gateway'      => $this->cleanString($payment_gateway_label),
          'payment_id'           => $payment_id,
        ];

      } else {
        // Handle the case where the payment gateway is not a valid entity.
        \Drupal::logger('optiback_export')->error('Invalid payment gateway for order @order_id.', ['@order_id' => $order->id()]);
      }
    } else {
      // Handle the case where the payment_gateway field is empty.
      \Drupal::logger('optiback_export')->warning('Payment gateway field is empty for order @order_id.', ['@order_id' => $order->id()]);
    }

   return $data;
 }

  /**
   * @param \Drupal\commerce_order\Entity\OrderItem $order_item
   * @return array
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

    // Check if the purchased entity is a ProductVariation.
    if (!$product_variation instanceof ProductVariationInterface) {
      return $data;
    }

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
    */
    $discount = $this->getDiscountData($order_item);
    $discount_val = floatval($discount['percentage']);

    // The data key's not relevant. The order must fit to the header.
    $data = [
      'order_id'  => $order_item->getOrderId(), // Optiback string
      'sku'       => $sku, // Optiback integer
      'quantity'  => number_format($quantity, 2, ',',''), // Optiback float
      'price'     => number_format($price, 3, ',',''), // Optiback float, 3 digit
      'discount'  => number_format($discount_val, 2, ',',''), // Optiback float
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
   * Gets discount data from order or order_item.
   *
   *
   * @param \Drupal\commerce_order\Entity\OrderItem $entiy
   *
   * @return int[]
   */
  public function getDiscountData($entiy) {

    $percentage = 0;

    // Possible discount values.
    $data = [
      'percentage' => 0,
      'amount' => 0,
    ];

    if (!$entiy instanceof Order && !$entiy instanceof OrderItem) {
      return $data;
    }

    $adjustments = $entiy->get('adjustments')->getValue();
    foreach ($adjustments as $adjustment) {
      $type = $adjustment['value']->getType();
      if ($adjustment['value']->getType() == 'promotion') {
        $percentage = $adjustment['value']->getPercentage() * 100;
        //$amount = $adjustment['value']->getAmount();
        //$discount = $amount->getNumber();
      }
    }

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
    if ($profile->hasField('field_company') && isset($profile->get('field_company')->getValue()[0])) {
      $data['company'] = $profile->get('field_company')->getValue()[0]['value'];
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
    }

    return $data;
  }

  /**
   * Creates csv file.
   */
  public function createCsvFile($header, $data, $filename) {
    $result = [];

    // Start using PHP's built in file handler functions to create a temporary file.
    $handle = fopen('php://temp', 'w+');

    // Add the header as the first line of the CSV.
    // We use tab delimiter \t.
    //fputcsv($handle, $header, "\t"," ");
    // Solution without enclosure which not possible with fputcsv.
    fwrite($handle, implode("\t", $header)."\n");

    foreach ($data as $row) {
      // Add the data we exported to the next line of the CSV>
      //fputcsv($handle, array_values($row), "\t");
      fwrite($handle, implode("\t", $row)."\n");
    }

    // Add's linebreak at the end of the file.
    fwrite($handle, PHP_EOL);

    // Reset where we are in the CSV.
    rewind($handle);

    // Retrieve the data from the file handler.
    $csv_data = stream_get_contents($handle);

    $result = file_put_contents(ObtibackConfigInterface::OPTIBACK_IN . '/' . $filename, $csv_data);

    fclose($handle);

    return $result;
  }

  /**
   * Callback funktion: Ceans string value.
   */
  public function cleanString($string) {

    $string = trim($string);

    $targetString = iconv( mb_detect_encoding( $string ), 'Windows-1252//TRANSLIT', $string );

    return $targetString;
  }
}
