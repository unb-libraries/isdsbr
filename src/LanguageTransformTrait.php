<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to transform MODS language elements into DC values.
 */
trait LanguageTransformTrait {

  private function transformLanguage() {
    foreach ($this->targetItemElements as $element) {
      $mods_value = $element->textContent;
      if (!empty($mods_value)) {
        $transformations = [
          'eng' => 'en_CA',
          'fre' => 'fr_CA',
        ];
        if (!empty($transformations[$mods_value])) {
          $this->targetItemValues[] = $transformations[$mods_value];
        }
        else {
          $this->targetItemValues[] = $mods_value;
        }
      }
    }
  }

}
