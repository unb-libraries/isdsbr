<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to transform MODS language elements into DC values.
 */
trait DegreeNameTransformTrait {

  private function degreeNameTransform() {
    foreach ($this->targetItemElements as $element) {
      $mods_value = $element->textContent;
      if (!empty($mods_value)) {
        $name_mapping = [
          'MA' => 'M.A.',
          'MA (Sport & Rec. Studies)' => 'M.A.S.R.C.',
          'MAHSR' => 'M.A.H.S.R.',
          'MBA' => 'M.B.A.',
          'MCS' => 'M.C.S.',
          'MCSC' => 'M.C.S.C.',
          'MEM' => 'M.E.M.',
          'MEd' => 'M.Ed.',
          'MEng' => 'M.Eng.',
          'MF' => 'M.F.',
          'MFE' => 'M.F.E.',
          'MIDST' => 'M.IDST.',
          'MN' => 'M.N.',
          'MPHIL' => 'M.Phil.',
          'MSc' => 'M.Sc.',
          'MSc (Exercise & Sport Science)' => 'M.Sc.',
          'MScE' => 'M.Sc.E.',
          'MScEM' => 'M.Sc.E.M.',
          'MScF' => 'M.Sc.F.',
          'MScFE' => 'M.Sc.F.E.',
          'PhD' => 'Ph.D.',
          'PhD IDST' => 'Ph.D.IDST.',
        ];
        if (array_key_exists($mods_value, $name_mapping)) {
          $this->targetItemValues[] = $name_mapping[$mods_value];
        }
        else {
          $this->targetItemValues[] = $mods_value;
        }
      }
    }
  }

  private function literalNursingNameTransform() {
    foreach ($this->targetItemElements as $element) {
      $this->targetItemValues[] = 'Master of Nursing';
    }
  }

  private function literalEngineeringNameTransform() {
    foreach ($this->targetItemElements as $element) {
      $this->targetItemValues[] = 'Bachelor of Engineering';
    }
  }

  private function literalUndergraduateLevelTransform() {
    foreach ($this->targetItemElements as $element) {
      $this->targetItemValues[] = 'undergraduate';
    }
  }

  private function literalMasterLevelTransform() {
    foreach ($this->targetItemElements as $element) {
      $this->targetItemValues[] = 'masters';
    }
  }

}
