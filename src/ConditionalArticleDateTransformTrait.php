<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to transform MODS subject elements into DC values.
 */
trait DateTransformTrait {

  private function transform8601DateDefaultNow() {
    $date_format = 'Y-m-d\TH:i:s\Z';
    foreach ($this->targetItemElements as $element) {
      $mods_value = trim($element->textContent);
      $tz = date_default_timezone_get();
      date_default_timezone_set('UTC');
      if (!empty($mods_value)) {
        $timestamp = strtotime($mods_value);
        $this->targetItemValues[] = date($date_format, $timestamp);
      } else {
        $this->targetItemValues[] = date($date_format, strtotime("now"));
      }
      date_default_timezone_set($tz);
    }
  }

}
