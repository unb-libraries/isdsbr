<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to transform MODS editor elements into DC values.
 */
trait EditorTransformTrait {

  private function transformEditor() {
    foreach ($this->targetItemElements as $element) {
      if ($this->nameElementIsEditor($element)) {
        $given_name = $this->getFirstChildElementValueByNameAttr($element, 'namePart', 'type', 'given');
        $family_name = $this->getFirstChildElementValueByNameAttr($element, 'namePart', 'type', 'family');
        if (!empty($given_name) && !empty($family_name)) {
          $this->targetItemValues[] = $this->formatEditorName($given_name, $family_name);
        }
      }
    }
  }

  private static function formatEditorName($given_name, $family_name) {
    return "$family_name, $given_name";
  }

  private function nameElementIsEditor($element) {
    if ($element->hasAttribute('type') && $element->getAttribute('type') == 'personal') {
      if ($this->roleElementIsEditor($element)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  private static function roleElementIsEditor($element) {
    $role_element = $element->getElementsByTagName('role')->item(0);
    $role_value = trim($role_element->textContent);
    if ($role_value == 'editor') {
      return TRUE;
    }
  }

}
