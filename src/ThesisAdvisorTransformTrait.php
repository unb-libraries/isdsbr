<?php

namespace UnbLibraries\IslandoraDspaceBridge;

use FullNameParser;

/**
 * Provides methods to transform MODS thesis advisor elements into DC values.
 */
trait ThesisAdvisorTransformTrait {

  private function transformSeniorAdvisor() {
    foreach ($this->targetItemElements as $element) {
      if ($this->nameElementIsSeniorAdvisor($element)) {
        $advisor_name = $this->getFirstChildElementValueByNameAttr($element, 'displayForm');
        if (!empty($advisor_name)) {
          $parser = new FullNameParser();
          $name_parts = $parser->parse_name($advisor_name);
          if (!empty($name_parts['fname']) && !empty($name_parts['lname'])) {
            $advisor_name = $this->formatAuthorName($name_parts['fname'], $name_parts['lname']);
          }
          $this->targetItemValues[] = $advisor_name;
        }
      }
    }
  }

  private function transformAdvisor() {
    foreach ($this->targetItemElements as $element) {
      if ($this->nameElementIsAdvisor($element)) {
        $advisor_name = $this->getFirstChildElementValueByNameAttr($element, 'displayForm');
        if (!empty($advisor_name)) {
          $parser = new FullNameParser();
          $name_parts = $parser->parse_name($advisor_name);
          if (!empty($name_parts['fname']) && !empty($name_parts['lname'])) {
            $advisor_name = $this->formatAuthorName($name_parts['fname'], $name_parts['lname']);
          }
          $this->targetItemValues[] = $advisor_name;
        }
      }
    }
  }

  private function nameElementIsAdvisor($element) {
    if ($element->hasAttribute('type') && $element->getAttribute('type') == 'personal') {
      if ($this->roleElementIsAdvisor($element)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  private static function roleElementIsAdvisor($element) {
    $role_element = $element->getElementsByTagName('role')->item(0);
    $role_value = trim($role_element->textContent);
    if ($role_value == 'Thesis advisor') {
      return TRUE;
    }
  }

  private function nameElementIsSeniorAdvisor($element) {
    if ($element->hasAttribute('type') && $element->getAttribute('type') == 'personal') {
      if ($this->roleElementIsSeniorAdvisor($element)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  private static function roleElementIsSeniorAdvisor($element) {
    $role_element = $element->getElementsByTagName('role')->item(0);
    $role_value = trim($role_element->textContent);
    if ($role_value == 'Senior Report advisor') {
      return TRUE;
    }
  }

}
