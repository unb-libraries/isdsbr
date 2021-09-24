<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides general methods to transform MODS elements into DC values.
 */
trait ElementTransformTrait {

  protected function searchChildElementsByName($node, $name, $attrs) {
    foreach ($node->childNodes as $child ) {
      if ( $child->nodeName == $name ) {
        return $child;
      }
    }
    return NULL;
  }

  protected function transformLiteral() {
    foreach ($this->targetItemElements as $element) {
      $text_content = $element->textContent;
      if (!empty($text_content)) {
        $this->targetItemValues[] = $text_content;
      }
    }
  }

  protected static function getFirstChildElementValueByNameAttr($elements, $name, $attr_tag = NULL, $attr_value = NULL) {
    foreach ($elements->getElementsByTagName($name) as $element) {
      if (!empty($attr_tag) && !empty($attr_value)) {
        if ($element->hasAttribute($attr_tag) && $element->getAttribute($attr_tag) === $attr_value) {
          return $element->textContent;
        }
      }
      else {
        return $element->textContent;
      }
    }
    return NULL;
  }

  protected static function getChildElementValuesByNameAttr($elements, $name, $attr_tag = NULL, $attr_value = NULL) {
    $values = [];
    foreach ($elements->getElementsByTagName($name) as $element) {
      if (!empty($attr_tag) && !empty($attr_value)) {
        if ($element->hasAttribute($attr_tag) && $element->getAttribute($attr_tag) === $attr_value) {
          $values[] = $element->textContent;
        }
      }
      else {
        $values[] = $element->textContent;
      }
    }
    return $values;
  }

}
