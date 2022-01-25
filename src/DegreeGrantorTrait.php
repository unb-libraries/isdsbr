<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to provide a literal string to an element.
 */
trait DegreeGrantorTrait {

  private function getLiteralDegreeGrantor() {
    foreach ($this->targetItemElements as $element) {
      $this->targetItemValues[] = 'University of New Brunswick';
    }
  }

}
