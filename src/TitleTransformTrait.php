<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to transform MODS title elements into DC values.
 */
trait TitleTransformTrait {

  private function transformTitle() {
    foreach ($this->targetItemElements as $element) {
      $title_value = '';
      $this->addLogNotice('Parsing title values');
      foreach ($element->getElementsByTagName('mods:title') as $spec_element) {
        $this->addLogNotice('Found Title!');
        $title_value = $spec_element->textContent;
      }
      foreach ($element->getElementsByTagName('mods:subTitle') as $spec_element) {
        $this->addLogNotice('Found SubTitle!');
        $title_value = $title_value . ' | ' . $spec_element->textContent;
      }
      $this->targetItemValues[] = $title_value;
    }
  }

}
