<?php
declare(strict_types=1);

namespace Drupal\custom_shipping;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_shipping\ShipmentManagerInterface;
use Drupal\commerce_shipping\ShippingOrderManagerInterface;
use Drupal\profile\Entity\Profile;

/**
 * Ensures there are shipments from the start.
 */
final class ShippingProcessor implements OrderProcessorInterface {

  /**
   * The shipping order manager.
   *
   * @var \Drupal\commerce_shipping\ShippingOrderManagerInterface
   */
  protected $shippingOrderManager;

  /**
   * The shipment manager.
   *
   * @var \Drupal\commerce_shipping\ShipmentManagerInterface
   */
  protected $shipmentManager;


  public function __construct(ShippingOrderManagerInterface $shippingOrderManager, ShipmentManagerInterface $shipmentManager) {
    $this->shippingOrderManager = $shippingOrderManager;
    $this->shipmentManager = $shipmentManager;
  }

  public function process(OrderInterface $order) {
    if ($order->isNew() || $this->shippingOrderManager->hasShipments($order)) {
      return;
    }

    $collected_profiles = $order->collectProfiles();
    $shipping_profile = $collected_profiles['shipping'] ?? NULL;
    // @todo remove and force specifying a zip code.
    if ($shipping_profile === NULL) {
      $shipping_profile = Profile::create([
        'type' => 'customer',
        'uid' => 0,
      ]);
    }

    $shipments = $this->shippingOrderManager->pack($order, $shipping_profile);
    foreach ($shipments as $shipment) {
      $rates = $this->shipmentManager->calculateRates($shipment);
      if (count($rates) > 0 ) {
        $rate = $this->shipmentManager->selectDefaultRate($shipment, $rates);
        $this->shipmentManager->applyRate($shipment, $rate);
      }
      $shipment->save();
    }
    $order->set('shipments', $shipments);
  }

}
