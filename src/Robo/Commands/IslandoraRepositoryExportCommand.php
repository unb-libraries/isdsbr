<?php

namespace UnbLibraries\IslandoraDspaceBridge\Robo\Commands;

use Exception;
use Robo\Robo;
use UnbLibraries\IslandoraDspaceBridge\Robo\Commands\IslandoraDspaceBridgeCommand;

/**
 * Provides commands to export objects from Islandora/Fedora based on a solr query.
 *
 * @TODO Documentation.
 */
class IslandoraRepositoryExportCommand extends IslandoraDspaceBridgeCommand {

  // const SOLR_INT_MAX = 2147483647;
  const SOLR_INT_MAX = 10;

  /**
   * @var array
   */
  protected $operations = [];

  /**
   * @var array
   */
  protected $needManualCopy = [];

  /**
   * @var string
   */
  protected $exportPath = NULL;

  protected $exportCollections;
  protected $exportIslandoraHostname;
  protected $exportSolrHostname;
  protected $exportSolrUri;
  protected $exportSolrCoreName;
  protected $exportFedoraJavaHome;
  protected $exportFedoraHome;
  protected $exportFedoraAdminUser;
  protected $exportFedoraAdminPass;
  protected $exportFedoraFormat;

  /**
   * Export objects from Islandora/Fedora based on a solr query.
   *
   * @param string $path
   *   The path to export the objects to.
   *
   * @option yes
   *   Assume a yes response for all prompts.
   *
   * @throws \Exception
   *
   * @command isdsbr:export
   */
  public function islandoraExportToLocal($path, $options = ['yes' => FALSE]) {
    $this->initExport($path);
    $this->setUpOperations();
    $this->exportObjects();
  }

  /**
   * @param $path
   *
   * @throws \Exception
   */
  protected function initExport($path) {
    $this->setUpExportPath($path);
    $this->setUpConfigValues();
  }

  protected function setUpExportPath($path) {
    $this->exportPath = $path;
    if ( $this->exportPath == NULL || !is_writable($this->exportPath)) {
      throw new Exception(
        sprintf(
          'The specified export path, %s is not writable',
          $path
        )
      );
    }
  }

  protected function setUpConfigValues() {
    $this->exportCollections = Robo::Config()->get('isdsbr.collections');
    $this->exportIslandoraHostname = Robo::Config()->get('isdsbr.fedora.hostname');
    $this->exportSolrHostname = Robo::Config()->get('isdsbr.solr.hostname');
    $this->exportSolrUri = Robo::Config()->get('isdsbr.solr.uri');
    $this->exportSolrCoreName = Robo::Config()->get('isdsbr.solr.core');
    $this->exportFedoraJavaHome = Robo::Config()->get('isdsbr.fedora.java_home');
    $this->exportFedoraHome = Robo::Config()->get('isdsbr.fedora.fedora_home');
    $this->exportFedoraAdminUser = Robo::Config()->get('isdsbr.fedora.admin_user');
    $this->exportFedoraAdminPass = Robo::Config()->get('isdsbr.fedora.admin_pass');
    $this->exportFedoraFormat = Robo::Config()->get('isdsbr.fedora.export_format');
  }

  /**
   *
   */
  protected function setUpOperations() {
    $this->doObjectDiscovery();
  }

  protected function doObjectDiscovery() {
    $this->addLogTitle('Object Discovery');
    foreach ($this->exportCollections as $collection_id => $collection) {
      $this->addLogStrong($collection['label']);
      $this->addLogNotice(
        sprintf(
          "[%s] Querying solr server for objects...",
          $this->exportSolrHostname
        )
      );
      $this->operations[$collection_id] = [
        'collection'=> $collection,
        'pid_list' => $this->getPidsFromQuery($collection['query'])
      ];
      $this->addLogNotice(
        sprintf(
          "[%s] %s objects found and queued for export...",
          $this->exportSolrHostname,
          count($this->operations[$collection_id]['pid_list'])
        )
      );
    }
  }

  protected function getPidsFromQuery($query) {
    $query_uri = sprintf(
      "%s/%s/select?%s&rows=%s&fl=PID&wt=csv&indent=true",
      $this->exportSolrUri,
      $this->exportSolrCoreName,
      $query,
      self::SOLR_INT_MAX
    );
    $query_command = "curl \"$query_uri\"";
    $this->addLogNotice(
      sprintf(
        '[%s] %s',
        $this->exportSolrHostname,
        $query_command
      )
    );
    $query_result = $this->taskSshExec($this->exportSolrHostname)
      ->exec($query_command)
      ->silent(TRUE)
      ->run();
    return $this->getPidsFromResult(
      $query_result->getMessage()
    );
  }

  protected function getPidsFromResult($result_string) {
    $results = explode("\n", $result_string);
    array_shift($results);
    return $results;
  }

  protected function exportObjects() {
    $this->addLogTitle('Object Export');
    foreach ($this->operations as $operation_idx => $operation) {
      $collection = $operation['collection'];
      $this->addLogStrong( $collection['label']);
      $operation_export_path = $this->exportPath . "/$operation_idx";
      if (!file_exists($operation_export_path)) {
        mkdir($operation_export_path, 0777, TRUE);
      }

      foreach ($operation['pid_list'] as $pid) {
        $this->addLogNotice("[{$this->exportIslandoraHostname}] Exporting PID $pid...");
        $export_file = $this->exportIslandoraItem($pid, $operation);
        $file_info = pathinfo($export_file);
        $temp_dir = $this->tempdir();
        $archive_path = "$temp_dir/{$file_info['filename']}";
        $this->transferObjectArchive($export_file, $archive_path);

        if (file_exists($archive_path) && filesize($archive_path)) {
          $local_tmp_path = $this->extractObjectArchive($archive_path, $temp_dir);
          $item_write_path = "$operation_export_path/$pid";
          if (!file_exists($item_write_path)) {
            mkdir($item_write_path, 0777, TRUE);
          }
          $this->xcopy($local_tmp_path, $item_write_path);
          file_put_contents("$item_write_path/PID", $pid);
          $this->delTree($local_tmp_path);
        } else {
          $this->needManualCopy[] = $pid;
        }
      }

      // Write mapping format.
      file_put_contents($operation_export_path . '/' . self::ISDSBR_FIELD_MAPPING_FILENAME, $operation['collection']['field_map']);

      // Write target collection filename.
      file_put_contents($operation_export_path . '/' . self::ISDSBR_TARGET_COLLECTION_FILENAME, $operation['collection']['target_collection']);

      // Report any issues.
      if (!empty($this->needManualCopy)) {
        $this->addLogTitle('Issues Detected!');
        $this->addLogNotice("Some items failed to export correctly. PDF.0.pdf and MODS.0.xml for each item will need to be created in {$this->exportPath} manually.");
        foreach ($this->needManualCopy as $pid) {
          $this->say("https://unbscholar.lib.unb.ca/islandora/object/$pid/manage/datastreams");
        }
      }
    }

  }

  /**
   * Delete an entire tree, including files.
   *
   * @author StackOverflow
   * @link https://stackoverflow.com/questions/4366730/how-do-i-check-if-a-string-contains-a-specific-word
   *
   * @param $dir
   *
   * @return bool|null
   */
  public static function delTree($dir) {
    if (strpos($dir, '/tmp') !== false) {
      $files = array_diff(scandir($dir), ['.', '..']);
      foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
      }
      return rmdir($dir);
    }
    return NULL;
  }

  /**
   * Copy files from one directory to another, recursively.
   *
   * @author      Aidan Lister <aidan@php.net>
   * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
   *
   * @param $source
   * @param $dest
   * @param int $permissions
   *
   * @return bool
   */
  function xcopy($source, $dest, $permissions = 0755) {
    $sourceHash = $this->hashDirectory($source);
    // Check for symlinks
    if (is_link($source)) {
      return symlink(readlink($source), $dest);
    }

    // Simple copy for a file
    if (is_file($source)) {
      return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
      mkdir($dest, $permissions);
    }

    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
      // Skip pointers
      if ($entry == '.' || $entry == '..') {
        continue;
      }

      // Deep copy directories
      if($sourceHash != $this->hashDirectory($source."/".$entry)) {
        $this->xcopy("$source/$entry", "$dest/$entry", $permissions);
      }
    }

    // Clean up
    $dir->close();
    return true;
  }

  /**
   * Copy a file, or recursively copy a folder and its contents
   * @author      Aidan Lister <aidan@php.net>
   * @version     1.0.1
   * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
   */
  function hashDirectory($directory){
    if (! is_dir($directory)){ return false; }
    $files = array();
    $dir = dir($directory);
    while (false !== ($file = $dir->read())){
      if ($file != '.' and $file != '..') {
        if (is_dir($directory . '/' . $file)) { $files[] = $this->hashDirectory($directory . '/' . $file); }
        else { $files[] = md5_file($directory . '/' . $file); }
      }
    }
    $dir->close();
    return md5(implode('', $files));
  }

  protected function exportIslandoraItem($pid, $operation) {
    return $this->generateExportArchive($pid);
  }

  protected function transferObjectArchive($export_file, $archive_path) {
    $this->taskRsync()
      ->fromPath("chimera:$export_file")
      ->toPath($archive_path)
      ->silent(TRUE)
      ->run();
  }

  protected function extractObjectArchive($archive_path, $temp_dir) {
    $zip = new \ZipArchive;
    if ($zip->open($archive_path) === TRUE) {
      $zip->extractTo($temp_dir);
      $zip->close();
    } else {
      $this->needManualCopy[] = $archive_path;
    }
    unlink($archive_path);
    return($temp_dir);
  }

  protected function generateExportArchive($pid) {
    $export_command = sprintf(
      'JAVA_HOME=%s FEDORA_HOME=%s ./fedora-export.sh localhost:8080 %s %s %s %s migrate /tmp http',
      $this->exportFedoraJavaHome,
      $this->exportFedoraHome,
      $this->exportFedoraAdminUser,
      $this->exportFedoraAdminPass,
      $pid,
      $this->exportFedoraFormat
    );
    $fedora_bin_dir = $this->exportFedoraHome . '/client/bin';
    $result = $this->taskSshExec($this->exportIslandoraHostname)
      ->exec($export_command)
      ->remoteDir($fedora_bin_dir)
      ->forcePseudoTty()
      ->silent(TRUE)
      ->run();
    return $this->getPathToArchive($result);
  }

  /**
   * @param $result
   *
   * @return mixed
   */
  protected function getPathToArchive($result) {
    $message = $result->getMessage();
    preg_match('/Exporting .* to (.*)/', $message, $matches);
    return $matches[1];
  }

  /**
   * @return false|string
   *
   * @throws \Exception
   */
  protected function tempdir() {
    $tempfile = tempnam(sys_get_temp_dir(),'');
    if (file_exists($tempfile)) { unlink($tempfile); }
    mkdir($tempfile);
    if (is_dir($tempfile)) { return $tempfile; }
    throw new Exception("Failure to create a temporary directory.");
  }

}
