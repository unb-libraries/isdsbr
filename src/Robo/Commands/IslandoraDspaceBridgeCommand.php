<?php

namespace UnbLibraries\IslandoraDspaceBridge\Robo\Commands;

use Psr\Log\LogLevel;
use Robo\Tasks;
use TrashPanda\ProgressBarLog\ProgressBarLog;

/**
 * Provides commands to export convert and import Islandora objects into Dspace.
 */
class IslandoraDspaceBridgeCommand extends Tasks {

  const ISDSBR_EXPORT_DIR_IDENTIFIER = 'MODS.0.xml';
  const ISDSBR_FIELD_MAPPING_FILENAME = 'isdsbr_field_map.txt';

  protected $progressBar;

  protected function setUpProgressBar() {
    $this->progressBar = new ProgressBarLog(50, 1);
    $this->progressBar->start();
  }

  protected function setProgressBarMaxValue($max_value) {
    $bar = $this->progressBar->getProgressBar();
    $bar->setMaxSteps($max_value);
  }

  protected function progressBarAdvance($num = 1) {
    $this->progressBar->advance($num);
  }

  protected function addLogCritical($message) {
    $this->progressBar->addLog(LogLevel::CRITICAL, $message);
  }

  protected function addLogNotice($message) {
    $this->progressBar->addLog(LogLevel::NOTICE, $message);
  }

}
