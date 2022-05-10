<?php

namespace UnbLibraries\IslandoraDspaceBridge;

use DOMXPath;

/**
 * Provides methods to conditionally provide a date if one isn't set.
 */
trait ConditionalArticleDateTransformTrait {

  private function transformHostDateIfNoModsDate() {
    if (!empty($this->files['dublin_core']['xml'])) {
      $xpath = new DomXpath($this->files['dublin_core']['xml']);
      foreach ($xpath->query('//dcvalue[@element="date" and @qualifier="issued"]') as $rowNode) {
        return;
      }
      $this->transformLiteral();
    }
  }

}
