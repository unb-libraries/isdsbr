<?php

namespace UnbLibraries\IslandoraDspaceBridge;

/**
 * Provides methods to transform MODS author elements into DC values.
 */
trait DepartmentGrantorTransformTrait {

  private function transformGrantor() {
    foreach ($this->targetItemElements as $element) {
      if ($this->nameElementIsGrantor($element)) {
        $names = $this->getChildElementValuesByNameAttr($element, 'namePart');
        $grantor_name_string = '';
        $grantor_dept_string = '';

        foreach ($names as $name) {
          if (!empty($name) && $this->grantorIsInstitution($name)) {
            $grantor_name_string = rtrim($name, '.');
          }
          if (!empty($name) && !$this->grantorIsInstitution($name)) {
            $grantor_dept_string = rtrim($name, '.');
          }
          if (!empty($grantor_name_string) && !empty($grantor_dept_string))
          $this->targetItemValues[] = "$grantor_name_string. $grantor_dept_string.";
        }
      }
    }
  }

  private function transformGrantorDepartment() {
    foreach ($this->targetItemElements as $element) {
      if ($this->nameElementIsGrantor($element)) {
        $names = $this->getChildElementValuesByNameAttr($element, 'namePart');
        foreach ($names as $name) {
          if (!empty($name) && !$this->grantorIsInstitution($name)) {
            $this->targetItemValues[] = rtrim($name, '.');
          }
        }
      }
    }
  }

  private function grantorIsInstitution($grantor) {
    if (str_contains(strtolower($grantor), 'university of new brunswick')) {
      return TRUE;
    }
    return FALSE;
  }

  private function nameElementIsGrantor($element) {
    if ($element->hasAttribute('type') && $element->getAttribute('type') == 'corporate') {
      if ($this->roleElementIsGrantor($element)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  private static function roleElementIsGrantor($element) {
    $role_element = $element->getElementsByTagName('role')->item(0);
    $role_value = trim($role_element->textContent);
    if ($role_value == 'Degree grantor') {
      return TRUE;
    }
  }

}
