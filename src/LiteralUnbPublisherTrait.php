<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to provide a literal string to an element.
 */
trait LiteralUnbPublisherTrait {

  private function getLiteralUNBPublisher() {
    foreach ($this->targetItemElements as $element) {
      $this->targetItemValues[] = 'University of New Brunswick';
    }
  }

}
