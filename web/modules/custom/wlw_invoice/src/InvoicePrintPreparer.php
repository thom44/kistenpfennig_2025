<?php

namespace Drupal\wlw_invoice;

use Drupal\commerce_invoice\Entity\InvoiceInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\commerce_price\Price;

/**
 * The invoice data preparer service.
 *   Prepares render array to generate pdf-invoice.
 *   This is used in
 *   - wlw_invoice.module wlw_theme_preprocess_commerce_invoice.
 *     Download over user interface.
 *   - \Drupal\wlw_invoice\InvoicePrintBuilder
 *     Pdf generation in order status transition validate.
 *     @see \Drupal\wlw_workflow\Mail\InvoiceFileAttachment
 */
class InvoicePrintPreparer implements InvoicePrintPreparerInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function preparePrintable(InvoiceInterface $invoice, $pdf_output_type = 'invoice') {

    $data = [];
    $invoice_items_data = [];
    $salut_list = [
      'Mr.' => 'Herr',
      'Mrs.' => 'Frau',
      'Firm' => 'Firma',
    ];
    $salutation = '';

    $data['invoice_entity'] = $invoice;
    //$uid = $invoice->getCustomerId();
    //$mail = $invoice->getEmail();

    // Get the order entity to the template.
    $orders = $invoice->getOrders();
    foreach ($orders as $order) {
      $data['order_entity'] = $order;
    }

    // Gets invoice items and loops through.
    $items = $invoice->getItems();
    $i=0;
    foreach ($items as $item) {
      // Saves the complete item to use some functions directly in the template.
      $invoice_items_data[$i]['item'] = $item;

      $order_item = $item->getOrderItem();
      if (!$order_item) {
        return;
      }

      // Retrieves purchased entity from invoice_item.
      $purchased_entity = $order_item->getPurchasedEntity();
      $field_tax_rate = $purchased_entity->get('field_tax_rate')[0]->getValue()['value'];

      // Retrieves tax_rate values from configuration object
      $tax = explode('|',$field_tax_rate);
      $tax_type = $tax[0];
      $tax_rate_id = $tax[1];
      $config_factory = \Drupal::service('config.factory');
      if ($config = $config_factory->get('commerce_tax.commerce_tax_type.' . $tax_type)) {
        foreach ($config->get('configuration')['rates'] as $rate) {
          if ($rate['id'] == $tax_rate_id) {
            $invoice_items_data[$i]['tax_label'] = $rate['label'];
            $tax_rate_value = $rate['percentage'];
            $invoice_items_data[$i]['tax_rate'] = $tax_rate_value*100;
          }
        }
      }


      // Prepares price Netto and tax amount.
      $price = $item->getTotalPrice();
      $price_value = $price->getNumber();
      $currency_code = $price->getCurrencyCode();

      if (isset($tax_rate_value)) {
        $tax_percentage = $tax_rate_value*100;
        $tax_amount = round(($price_value/(100+$tax_percentage))*$tax_percentage,2);
        // Creates an price object to use the commerce_price_formatter in the template.
        $invoice_items_data[$i]['tax_amount'] = new Price($tax_amount, $currency_code);

        $net_amount = round($price_value-$tax_amount,2);
        $invoice_items_data[$i]['net_amount'] = new Price($net_amount, $currency_code);
      }

      // Prepares array invoice_items_data for loop in template.
      $data['invoice_items_data'] = $invoice_items_data;
      $i++;
    }

    // Creates adjustments array and adds percantage to tax label.
    /** @var \Drupal\commerce_invoice\InvoiceTotalSummaryInterface $invoice_total_summary */
    $invoice_total_summary = \Drupal::service('commerce_invoice.invoice_total_summary');
    $totals = $invoice_total_summary->buildTotals($invoice);
    foreach ($totals['adjustments'] as $key => $adjustment) {
      if ($adjustment['type'] == 'tax') {
        $totals['adjustments'][$key]['label'] = 'Enthält ' . ($adjustment['percentage']*100) . '% ' . $adjustment['label'];
      }
    }
    $data['totals'] = $totals;

    // Gets the billing profile and creates salutation.
    if ($invoice->getBillingProfile()) {
      $profile_view_bulder = \Drupal::entityTypeManager()->getViewBuilder('profile');
      $billing_information = $profile_view_bulder->view($invoice->getBillingProfile());

      // Retrieves salutation from the billing_profile.
      $profile = $billing_information['#profile'];
      if ($profile->get('field_salutation')[0]) {
        $salut_key = $profile->get('field_salutation')[0]->getValue()['value'];
        $salutation = $salut_list[$salut_key];
      }


      // Retrieves last name from the billing_profile.
      $name = $profile->get('address')[0]->getValue()['family_name'];

      $data['invoice']['salutation'] = 'Sehr geehrte(r) ' . $salutation . ' ' . $name;

      $data['invoice']['billing_information'] = $billing_information;
    }

    // WLW Logo
    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('wlw_invoice')->getPath();
    $host = \Drupal::request()->getSchemeAndHttpHost();
    $data['logo_url'] =  $host . '/' . $module_path . '/images/wlw-logo.jpg';

    // Sets variables for credit (Gutschrift).
    if ($pdf_output_type == 'credit') {
      $data['invoice']['message']['top'] = $this->t('für die von Ihnen zurückerhaltenen Artikel schreiben wir Ihnen folgenden Betrag gut:');
      $data['credit']['message']['bottom'] = $this->t('Den oben genannten Betrag werden wir auf das Ausgangskonto zurücküberweisen.');
      $data['credit']['title'] = $this->t('Gutschrift zur Rechnung');
    } else {
      // Sets variables for invoice (Rechnung).
      $data['invoice']['message']['top'] = $this->t('für die von Ihnen bestellten Artikel stellen wir Ihnen in Rechnung:');
      $data['invoice']['message']['bottom'] = $this->t('Den oben genannten Betrag haben wir am');
      $data['invoice']['message']['post'] = $this->t('dankend erhalten.');
    }

    return $data;
  }
}
