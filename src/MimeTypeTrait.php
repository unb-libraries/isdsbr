<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to provide a literal string to an element.
 */
trait MimeTypeTrait {

  private function getLiteralMimeType() {
    foreach ($this->targetItemElements as $element) {
      $this->targetItemValues[] = 'text/xml';
    }
  }

}
