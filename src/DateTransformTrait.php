<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to transform MODS subject elements into DC values.
 */
trait DateTransformTrait {

  private function transform8601Date() {
    foreach ($this->targetItemElements as $element) {
      $mods_value = trim($element->textContent);
      if (!empty($mods_value)) {
        $timestamp = strtotime($mods_value);
        $this->targetItemValues[] = date('c', $timestamp);
      }
    }
  }

}
