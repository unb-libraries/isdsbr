<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to transform MODS genre elements into controlled values.
 */
trait ArticleTypeTransformTrait {

  private function articleTypeTransform() {
    foreach ($this->targetItemElements as $element) {
      $mods_value = $element->textContent;
      if (!empty($mods_value)) {
        $type_mapping = [
          'Article' => 'journal article',
          'Case Study' => 'report',
          'Evaluation Report' => 'report',
          'Report' => 'report',
          'Technical Report' => 'technical report',
          'Working Paper' => 'working paper',
          'Video' => 'video',
          'Book' => 'book',
          'Book Chapter' => 'book part',
        ];
        if (array_key_exists($mods_value, $type_mapping)) {
          $this->targetItemValues[] = $type_mapping[$mods_value];
        }
        else {
          $this->targetItemValues[] = 'article';
        }
      }
    }
  }

}
