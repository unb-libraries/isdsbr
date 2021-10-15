<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to transform MODS title elements into DC values.
 */
trait TitleTransformTrait {

  private function transformTitle() {
    foreach ($this->targetItemElements as $element) {
      $title_values = [];
      $subtitle_values = [];

      foreach ($element->childNodes as $spec_element) {
        if ($spec_element->nodeName == 'mods:title') {
          $title_values[] = $spec_element->textContent;
        }
        if ($spec_element->nodeName == 'mods:subTitle') {
          $subtitle_values[] = $spec_element->textContent;
        }
      }

      $final_string = implode(" ", $title_values);
      if (!empty($subtitle_values)) {
        $subtitle_string = trim(implode(" ", $subtitle_values));
        if (!empty($subtitle_string)) {
          $final_string .= ": $subtitle_string";
        }
      }
      $this->targetItemValues[] = implode(" ", $title_values);
    }
  }

}
