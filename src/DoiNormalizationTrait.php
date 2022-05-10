<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to provide a literal string to an element.
 */
trait DoiNormalizationTrait {

  private function getStandardizedDoiValue() {
    foreach ($this->targetItemElements as $element) {
      $text_content = $element->textContent;
      if (!empty($text_content)) {
        $this->targetItemValues[] = str_ireplace(
          [
            'https://dx.doi.org/',
            'http://dx.doi.org/',
            'https://doi.org/',
            'http://doi.org/',
          ],
          '',
          $text_content
        );
      }
    }
  }

}
