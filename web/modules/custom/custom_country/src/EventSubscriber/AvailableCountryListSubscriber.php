<?php

namespace Drupal\custom_country\EventSubscriber;

use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\AvailableCountriesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AvailableCountryListSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    $events[AddressEvents::AVAILABLE_COUNTRIES][] = ['onAvailableCountries'];
    return $events;
  }

  public function onAvailableCountries(AvailableCountriesEvent $event) {
    $countries = [
      'DE' => 'DE',
      'AT' => 'AT'
    ];
    $event->setAvailableCountries($countries);
  }

}
