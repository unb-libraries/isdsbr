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

  /**
   * The progress bar object for the current import.
   *
   * @var \TrashPanda\ProgressBarLog\ProgressBarLog
   */
  protected $progressBar;

  /**
   * Sets up the progress bar for use.
   */
  protected function setUpProgressBar() {
    $this->progressBar = new ProgressBarLog(50, 1);
    $this->progressBar->start();
  }

  /**
   * Sets a maximum value for the progress bar.
   *
   * @param int $max_value
   *   The maximum value to set.
   */
  protected function setProgressBarMaxValue($max_value) {
    $bar = $this->progressBar->getProgressBar();
    $bar->setMaxSteps($max_value);
  }

  /**
   * Advances the progress bar.
   *
   * @param int $num
   *   The amount of steps to advance the bar. Defaults to 1.
   */
  protected function progressBarAdvance($num = 1) {
    $this->progressBar->advance($num);
  }

  /**
   * Adds a CRITICAL level message to the progress bar logger.
   *
   * @param $message
   *   The message to add.
   */
  protected function addLogCritical($message) {
    $this->progressBar->addLog(LogLevel::CRITICAL, $message);
  }

  /**
   * Adds a NOTICE level message to the progress bar logger.
   *
   * @param $message
   *   The message to add.
   */
  protected function addLogNotice($message) {
    $this->progressBar->addLog(LogLevel::NOTICE, $message);
  }

}
