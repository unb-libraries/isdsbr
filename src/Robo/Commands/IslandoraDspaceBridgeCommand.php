<?php

namespace UnbLibraries\IslandoraDspaceBridge\Robo\Commands;

use Psr\Log\LogLevel;
use Robo\Robo;
use Robo\Tasks;
use TrashPanda\ProgressBarLog\ProgressBarLog;

/**
 * Provides commands to export convert and import Islandora objects into Dspace.
 */
class IslandoraDspaceBridgeCommand extends Tasks {

  const ISDSBR_EXPORT_DIR_IDENTIFIER = 'MODS.0.xml';
  const ISDSBR_FIELD_MAPPING_FILENAME = '.isdsbr_field_map';
  const ISDSBR_TARGET_COLLECTION_FILENAME = '.isdsbr_target_collection';

  /**
   * Adds a CRITICAL level message to the progress bar logger.
   *
   * @param $message
   *   The message to add.
   */
  protected function addLogCritical($message) {
    $this->io()->error($message);
  }

  /**
   * Adds a NOTICE level message to the progress bar logger.
   *
   * @param $message
   *   The message to add.
   */
  protected function addLogNotice($message) {
    $this->io()->text($message);
  }

  /**
   * Adds a strong message to the progress bar logger.
   *
   * @param $message
   *   The message to add.
   */
  protected function addLogStrong($message) {
    $this->io()->note($message);
  }

  /**
   * Adds a title message to the progress bar logger.
   *
   * @param $message
   *   The message to add.
   */
  protected function addLogTitle($message) {
    $this->io()->title($message);
  }

  /**
   * Gets the current logger.
   *
   * @return \Robo\Symfony\ConsoleIO|\Symfony\Component\Console\Style\SymfonyStyle
   */
  public function logger() {
    return $this->io();
  }

}
