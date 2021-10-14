<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to transform MODS author elements into DC values.
 */
trait AuthorTransformTrait {

  private function transformAuthor() {
    foreach ($this->targetItemElements as $element) {
      if ($this->nameElementIsAuthor($element)) {
        $given_name = $this->getFirstChildElementValueByNameAttr($element, 'namePart', 'type', 'given');
        $family_name = $this->getFirstChildElementValueByNameAttr($element, 'namePart', 'type', 'family');
        if (!empty($given_name) && !empty($family_name)) {
          $this->targetItemValues[] = $this->formatAuthorName($given_name, $family_name);
        }
      }
    }
  }

  private static function formatAuthorName($given_name, $family_name) {
    return "$family_name, $given_name";
  }

  private function nameElementIsAuthor($element) {
    if ($element->hasAttribute('type') && $element->getAttribute('type') == 'personal') {
      if ($this->roleElementIsAuthor($element)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  private static function roleElementIsAuthor($element) {
    $role_element = $element->getElementsByTagName('role')->item(0);
    $role_value = trim($role_element->textContent);
    if ($role_value == 'author') {
      return TRUE;
    }
  }

}
