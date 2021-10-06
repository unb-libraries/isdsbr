<?php

namespace UnbLibraries\IslandoraDspaceBridge\Robo\Commands;

use Exception;
use Robo\Robo;
use Robo\Tasks;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Finder\Finder;

/**
 * Provides commands to import  from Islandora/Fedora based on a solr query.
 */
class DspaceImportCommand extends Tasks {

  const PROGRESS_BAR_FORMAT = '';
  const EXPORT_DIR_IDENTIFIER = 'dublin_core.xml';
  const IMPORT_ZIP_PATH = '/tmp';
  const IMPORT_LOCAL_MAP_PATH = 'import_maps';
  const DSPACE_BIN_PATH = '/dspace/bin/dspace';
  const DSPACE_ADMIN_USER = 'jsanford@unb.ca';

  /**
   * @var string
   */
  protected $importPath = NULL;
  protected $dspacePodId = NULL;
  protected $dspacePodNamespace = NULL;
  protected $dspaceCollection = NULL;
  protected $importTimeStamp = NULL;
  protected $importZipFilePath = NULL;
  protected $importZipFileName = NULL;
  protected $importMapFileName = NULL;
  protected $importLocalMapPath = NULL;

  /**
   * Import objects from a simple archive format tree to DSpace.
   *
   * @param string $path
   *   The path to the Simple Archive Format tree.
   * @param string $kubectl_pod_id
   *   The DSpace pod ID to target
   * @param string $kubectl_deployment_env
   *   The DSpace deployment environment
   * @param string $dspace_collection
   *   The DSpace collection to import to
   *
   * @option yes
   *   Assume a yes response for all prompts.
   *
   * @throws \Exception
   *
   * @command isdsbr:import
   */
  public function dspaceImportData($path, $kubectl_pod_id, $kubectl_deployment_env, $dspace_collection, $opts = ['yes' => FALSE]) {
    $this->importPath = $path;
    $this->dspacePodNamespace = $kubectl_deployment_env;
    $this->dspacePodId = $kubectl_pod_id;
    $this->dspaceCollection = $dspace_collection;
    $this->importTimeStamp = time();
    $this->importZipFileName = "isdsbr_{$this->importTimeStamp}.zip";
    $this->importZipFilePath = self::IMPORT_ZIP_PATH . "/{$this->importZipFileName}";
    $this->importMapFileName = 'dspace_import_map_' . $this->importTimeStamp . '.txt';
    $this->importLocalMapPath = getcwd() . '/' . self::IMPORT_LOCAL_MAP_PATH;
    
    $this->initImport();
    $this->validateImportFolder();
    $this->zipImportFolder();
    $this->copyZipToContainer();
    $this->importContainerZip();
    $this->executeFilterMedia();
    $this->copyMapFile();
  }

  /**
   * Revert a previously imported set of data using a local mapfile.
   *
   * @param string $timestamp
   *   The timestamp of the import to revert.
   * @param string $kubectl_pod_id
   *   The DSpace pod ID to target
   * @param string $kubectl_deployment_env
   *   The DSpace deployment environment
   *
   * @option yes
   *   Assume a yes response for all prompts.
   *
   * @throws \Exception
   *
   * @command isdsbr:import:revert
   */
  public function dspaceRevertImport($timestamp, $kubectl_pod_id, $kubectl_deployment_env, $opts = ['yes' => FALSE]) {
    $this->dspacePodNamespace = $kubectl_deployment_env;
    $this->dspacePodId = $kubectl_pod_id;
    $this->importTimeStamp = $timestamp;
    $this->importMapFileName = 'dspace_import_map_' . $this->importTimeStamp . '.txt';
    $this->importLocalMapPath = getcwd() . '/' . self::IMPORT_LOCAL_MAP_PATH;
    $this->copyMapFileToContainer();
    $this->revertImport();
  }

  private function revertImport() {
    $this->io()->title('Reverting Import');
    $dspace_bin = self::DSPACE_BIN_PATH;
    $cmd = "kubectl exec -t {$this->dspacePodId} --namespace={$this->dspacePodNamespace} -- $dspace_bin import --eperson=" . self::DSPACE_ADMIN_USER . " -d -m /tmp/{$this->importMapFileName}";
    $this->say($cmd);
    passthru($cmd);
  }

  private function copyMapFileToContainer() {
    $this->io()->title('Copying Map File To Local');
    $cmd = "kubectl cp {$this->importLocalMapPath}/{$this->importMapFileName} {$this->dspacePodId}:/tmp/{$this->importMapFileName} --namespace={$this->dspacePodNamespace}";
    $this->say($cmd);
    passthru($cmd);
  }


  /**
   * @param $path
   *
   * @throws \Exception
   */
  private function initImport() {
    if ($this->importPath == NULL || !is_writable($this->importPath)) {
      throw new Exception(
        sprintf(
          'The specified import path, %s is not writable',
          $this->importPath
        )
      );
    }
  }

  private function validateImportFolder() {
    $finder = new Finder();
    $finder->files()->in($this->importPath)->name(self::EXPORT_DIR_IDENTIFIER);
    foreach ($finder as $file) {
      return;
    }
    throw new Exception(
      sprintf(
        'The specified import path, %s does not appear to contain any items.',
        $this->importPath
      )
    );
  }

  private function zipImportFolder() {
    $this->io()->title('Archiving Files Prior to Transfer');
    $cmd = "cd {$this->importPath}; zip -r {$this->importZipFilePath} *";
    $this->say($cmd);
    passthru($cmd);
  }

  private function copyZipToContainer() {
    $this->io()->title('Copying Zip file to container');
    $cmd = "kubectl --v=6 cp {$this->importZipFilePath} {$this->dspacePodId}:{$this->importZipFilePath} --namespace={$this->dspacePodNamespace}";
    $this->say($cmd);
    passthru($cmd);
  }

  private function importContainerZip() {
    $this->io()->title('Importing Archive Format');
    $dspace_bin = self::DSPACE_BIN_PATH;
    $mapfile = '/tmp/' . $this->importMapFileName;
    $cmd = "kubectl exec -t {$this->dspacePodId} --namespace={$this->dspacePodNamespace} -- $dspace_bin import --add --collection={$this->dspaceCollection} --eperson=" . self::DSPACE_ADMIN_USER . " --source=" . self::IMPORT_ZIP_PATH . " --zip={$this->importZipFileName} --mapfile=$mapfile";
    $this->say($cmd);
    passthru($cmd);
  }

  private function executeFilterMedia() {
    $this->io()->title('Executing dspace filter-media on collection');
    $dspace_bin = self::DSPACE_BIN_PATH;
    $cmd = "kubectl exec -t {$this->dspacePodId} --namespace={$this->dspacePodNamespace} -- $dspace_bin filter-media -i {$this->dspaceCollection}";
    $this->say($cmd);
    passthru($cmd);
  }

  private function copyMapFile() {
    $this->io()->title('Copying Map File To Local');
    $cmd = "kubectl cp {$this->dspacePodId}:/tmp/{$this->importMapFileName} {$this->importLocalMapPath}/{$this->importMapFileName} --namespace={$this->dspacePodNamespace}";
    $this->say($cmd);
    passthru($cmd);
  }

}
