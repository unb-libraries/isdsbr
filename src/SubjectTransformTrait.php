<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to transform MODS subject elements into DC values.
 */
trait SubjectTransformTrait {

  private function transformSubject() {
    $subject_strings = [];
    foreach ($this->targetItemElements as $element) {
      $mods_value = trim($element->textContent);
      if (!empty($mods_value)) {
        $subject_strings[] = $mods_value;
      }
    }
    if (!empty($subject_strings)) {
      $this->targetItemValues[] = implode('||', $subject_strings);
    }
  }

}
