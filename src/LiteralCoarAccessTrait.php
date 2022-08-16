<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to provide a literal string to an element.
 */
trait LiteralCoarAccessTrait {

  private function getLiteralCoarAccessRestricted() {
    foreach ($this->targetItemElements as $element) {
      $this->targetItemValues[] = 'http://purl.org/coar/access_right/c_16ec';
    }
  }

  private function getLiteralCoarAccessOpen() {
    foreach ($this->targetItemElements as $element) {
      $this->targetItemValues[] = 'http://purl.org/coar/access_right/c_abf2';
    }
  }

  private function getLiteralCoarAccessMetadataOnly() {
    foreach ($this->targetItemElements as $element) {
      $this->targetItemValues[] = 'http://purl.org/coar/access_right/c_14cb';
    }
  }

}
