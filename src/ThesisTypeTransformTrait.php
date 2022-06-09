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
        $type_mapping = [
          'undergraduate' => 'bachelor thesis',
          'masters' => 'master thesis',
          'doctoral' => 'doctoral thesis',
        ];
        if (array_key_exists($mods_value, $type_mapping)) {
          $this->targetItemValues[] = $type_mapping[$mods_value];
        }
        else {
          $this->targetItemValues[] = 'Electronic Thesis or Dissertation';
        }
      }
    }
  }

  private function literalNursingTypeTransform() {
    foreach ($this->targetItemElements as $element) {
      $this->targetItemValues[] = 'nursing synthesis paper';
    }
  }

  private function literalHonoursTypeTransform() {
    foreach ($this->targetItemElements as $element) {
      $this->targetItemValues[] = 'bachelor thesis';
    }
  }

  private function literalSeniorTypeTransform() {
    foreach ($this->targetItemElements as $element) {
      $this->targetItemValues[] = 'senior report';
    }
  }
}
