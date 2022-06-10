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
          'Bachelor of Arts' => 'B.A.',
          'Bachelor of Arts in Sociology' => 'B.A.',
          'Bachelor of Computer Science' => 'B.C.S.',
          'Bachelor of Forestry' => 'B.F.',
          'Bachelo of Science' => 'B.Sc.',
          'Bachelor of Science' => 'B.Sc.',
          'Bachelor of Science in Earth Sciences' => 'B.Sc.',
          'Bachelor of Science in Geology' => 'B.Sc.',
          'Bachelor of Science in Physics' => 'B.Sc.',
          'Bachelor of Science inEarth Sciences.' => 'B.Sc.',
          'Bachelor of Science with Honours in Biology' => 'B.Sc.',
          'Bachelor of Science with Honours in Biology-Psychology' => 'B.Sc.',
          'Bachelor of Science with Honours in Biology; Minor in Chemistry' => 'B.Sc.',
          'Bachelor of Science with Honours in General Biology' => 'B.Sc.',
          'Bachelor of Science with Honours in Geology' => 'B.Sc.',
          'Bachelor of Science with Honours in Marine Biology' => 'B.Sc.',
          'Bachelor of Science with Honours in Marine biology' => 'B.Sc.',
          'Bachelor of Science, Honours (Earth Sciences - Physics)' => 'B.Sc.',
          'Bachelor’s of Science' => 'B.Sc.',
          'Baxchelor of Science in Physics' => 'B.Sc.',
          'Bachelor of Science and Engineering' => 'B.Sc.E.',
          'Bachelor of Science in Engineering' => 'B.Sc.E.',
          'Bachelor of Science in Forestry and Environmental Management' => 'B.Sc.E.M.',
          'Bachelor of Science in Forestry' => 'B.Sc.F',
          'bachelor of Science in Forestry' => 'B.Sc.F',
          'Bachelor of Science in Forest Engineering' => 'B.Sc.F.E.',
          'Master of Arts' => 'M.A.',
          'Master of Arts in Classics' => 'M.A.',
          'Master of Arts in Classics and Ancient History' => 'M.A.',
          'Master of Arts in Economics' => 'M.A.',
          'Master of Arts in History' => 'M.A.',
          'Master of Arts in Political Science' => 'M.A.',
          'Master of Arts in Sociology' => 'M.A.',
          'Masters in Arts' => 'M.A.',
          'Master in Applied Health Services Research' => 'M.A.H.S.R.',
          'Master of Applied Health Research' => 'M.A.H.S.R.',
          'Master of Applied Health Service Research' => 'M.A.H.S.R.',
          'Master of Applied Health Services Research' => 'M.A.H.S.R.',
          'Master\'s in Applied Health Services Research' => 'M.A.H.S.R.',
          'Master’s in Applied Health Services Research' => 'M.A.H.S.R.',
          'Master of Arts (Sports and Recreation Studies)' => 'M.A.S.R.C.',
          'Master of Arts in Sport & Recreation Studies' => 'M.A.S.R.C.',
          'Master of Arts in Sport and Recreation Studies' => 'M.A.S.R.C.',
          'Master of Arts in Sports and Recreation Studies' => 'M.A.S.R.C.',
          'Master in Business Administration' => 'M.B.A.',
          'Master of Business Administration' => 'M.B.A.',
          'Master of Computer Science' => 'M.C.S.',
          'Master of Computer Science Coop' => 'M.C.S.',
          'Master of Computer Science.' => 'M.C.S.',
          'Masters of Computer Science' => 'M.C.S.',
          'Master of Environmental Management' => 'M.E.M.',
          'Master of Education' => 'M.Ed.',
          'Master of Education (Counselling)' => 'M.Ed.',
          'Master of Education (Critical Studies)' => 'M.Ed.',
          'Master of Education (Curriculum Studies)' => 'M.Ed.',
          'Master of Education (Exceptional Learners)' => 'M.Ed.',
          'Master of Education in Counselling' => 'M.Ed.',
          'Master of Education in Critical Studies' => 'M.Ed.',
          'Master of Education in Curriculum Studies' => 'M.Ed.',
          'Masters of Education, Counselling Psychology' => 'M.Ed.',
          'Master of Engineering' => 'M.Eng.',
          'Master of Engineering in Civil Engineering' => 'M.Eng.',
          'Master of Engineering.' => 'M.Eng.',
          'Master of Mechanical Engineering' => 'M.Eng.',
          'Master of Forestry' => 'M.F.',
          'Master of Science in Forestry' => 'M.F.',
          'Master of Forest Engineering' => 'M.F.E.',
          'Master of Forestry Engineering' => 'M.F.E.',
          'Master of Science in Forest Engineering' => 'M.F.E.',
          'Master of Science in Forestry Engineering' => 'M.F.E.',
          'Master of Forestry in Forestry and Environmental Management' => 'M.F.E.M.',
          'Master of Interdisciplinary Studies' => 'M.IDST.',
          'Master of Nursing' => 'M.N.',
          'Masters in Nursing' => 'M.N.',
          'Master Philosophy of Policy Studies' => 'M.Phil',
          'Master of Philosophy' => 'M.Phil.',
          'Master of Philosophy in Policy Studies' => 'M.Phil.',
          'Master of Science' => 'M.Sc.',
          'Master of Science in Biological Sciences' => 'M.Sc.',
          'Master of Science in Biology' => 'M.Sc.',
          'Master of Science in Chemistry' => 'M.Sc.',
          'Master of Science in Earth Sciences' => 'M.Sc.',
          'Master of Science in Geology' => 'M.Sc.',
          'Master of Science in Kinesiology' => 'M.Sc.',
          'Master of Science in Mathematics' => 'M.Sc.',
          'Master of Science in Mathematics and Statistics' => 'M.Sc.',
          'Masters of Science' => 'M.Sc.',
          'Masters of Science in Exercise and Sport Science' => 'M.Sc.',
          'Master of Science in Chemical Engineering' => 'M.Sc.E.',
          'Master of Science in Civil' => 'M.Sc.E.',
          'Master of Science in Engineering' => 'M.Sc.E.',
          'Master of Science in Engineering.' => 'M.Sc.E.',
          'Master of Science in in Mechanical Engineering' => 'M.Sc.E.',
          'Master of Science in Mechanical Engineering' => 'M.Sc.E.',
          'Master of Scince in Engineering' => 'M.Sc.E.',
          'Masters in Science of Engineering' => 'M.Sc.E.',
          'Masters of Science in Electrical Engineering' => 'M.Sc.E.',
          'Master of Science in Environmental Management' => 'M.Sc.E.M.',
          'Master of Science (Exercise and Sport Science)' => 'M.Sc.E.S.S.',
          'Master of Science in Exercise & Sport Science' => 'M.Sc.E.S.S.',
          'Master of Science in Exercise and Sport Science' => 'M.Sc.E.S.S.',
          'Master of Science in Exercise and Sport Science in Kinesiology' => 'M.Sc.E.S.S.',
          'Master of Science in Exercise and Sport Sciences' => 'M.Sc.E.S.S.',
          'Master of Science in Exercise Sport Science' => 'M.Sc.E.S.S.',
          'Doctor in Philosophy' => 'Ph.D.',
          'Doctor of Philosophy' => 'Ph.D.',
          'Doctor of Philosophy in Biology' => 'Ph.D.',
          'Doctor of Philosophy in Chemical Engineering' => 'Ph.D.',
          'Doctor of Philosophy in Civil Engineering' => 'Ph.D.',
          'Doctor of Philosophy in Computer Science' => 'Ph.D.',
          'Doctor of Philosophy in Education' => 'Ph.D.',
          'Doctor of Philosophy in Electrical and Computer Engineering' => 'Ph.D.',
          'Doctor of Philosophy in Engineering' => 'Ph.D.',
          'Doctor of Philosophy in Forestry and Environmental Management' => 'Ph.D.',
          'Doctor of Philosophy in Geodesy and Geomatics Engineering' => 'Ph.D.',
          'Doctor of Philosophy in History' => 'Ph.D.',
          'Doctor of Philosophy in Interdisciplinary Studies' => 'Ph.D.',
          'Doctor of Philosophy in Mechanical Engineering' => 'Ph.D.',
          'Doctor of Philosophy in Physics' => 'Ph.D.',
          'Doctor of Philosophy in Psychology' => 'Ph.D.',
          'Doctor of Philosophy, Experimental and Applied Psychology' => 'Ph.D.',
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
