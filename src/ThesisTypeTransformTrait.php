<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to transform MODS language elements into DC values.
 */
trait ThesisTypeTransformTrait {

  private function thesisTypeTransform() {
    foreach ($this->targetItemElements as $element) {
      $mods_value = $element->textContent;
      if (!empty($mods_value)) {
        $this->targetItemValues[] = 'Electronic Thesis or Dissertation';
      }
    }
  }

}
