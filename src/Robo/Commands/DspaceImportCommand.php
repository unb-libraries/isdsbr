<?php

namespace UnbLibraries\IslandoraDspaceBridge\Robo\Commands;

use Exception;
use Symfony\Component\Finder\Finder;

/**
 * Provides commands to import a Simple Archive Format objects into DSpace.
 */
class DspaceImportCommand extends IslandoraDspaceBridgeCommand {

  const DSPACE_ADMIN_USER = 'jsanford@unb.ca';
  const DSPACE_BIN_PATH = '/dspace/bin/dspace';
  const EXPORT_DIR_IDENTIFIER = 'dublin_core.xml';
  const IMPORT_LOCAL_MAP_PATH = 'import_maps';
  const IMPORT_ZIP_PATH = '/tmp';

  /**
   * The DSpace collection ID/handle to target.
   *
   * @var string
   */
  protected $dspaceTargetCollectionHandle;

  /**
   * The k8s pod ID to target.
   *
   * @var string
   */
  protected $dspacePodId;

  /**
   * The k8s pod namespace to target.
   *
   * @var string
   */
  protected $dspacePodNamespace;

  /**
   * The DSpace import map file path.
   *
   * @var string
   */
  protected $importLocalMapPath;

  /**
   * The DSpace import map file name.
   *
   * @var string
   */
  protected $importMapFileName;

  /**
   * The path to the Simple Archive Format root to import.
   *
   * @var string
   */
  protected $importPath;

  /**
   * The timestamp to use when referencing this import.
   *
   * @var string
   */
  protected $importTimeStamp;

  /**
   * The entire import's ZIP archive filename.
   *
   * @var string
   */
  protected $importZipFileName;

  /**
   * The entire import's ZIP archive filepath.
   *
   * @var string
   */
  protected $importZipFilePath;

  /**
   * Imports objects from a simple archive format tree to DSpace.
   *
   * @param string $path
   *   The path to the Simple Archive Format tree.
   * @param string $kubectl_pod_id
   *   The DSpace pod ID to target
   * @param string $kubectl_deployment_env
   *   The DSpace deployment environment
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option yes
   *   Assume a yes response for all prompts.
   *
   * @throws \Exception
   *
   * @command isdsbr:import
   * @TODO Clean this nonsense up.
   */
  public function dspaceImportData($path, $kubectl_pod_id, $kubectl_deployment_env, $options = ['yes' => FALSE]) {
    $this->dspacePodNamespace = $kubectl_deployment_env;
    $this->dspacePodId = $kubectl_pod_id;
    $this->importTimeStamp = time();

    $dir_finder = new Finder();
    $dir_finder->directories()->in($path)->depth(0);
    foreach ($dir_finder as $import_dir) {
      $this->importPath = $import_dir->getPathname();
      $import_slug = $import_dir->getFilename();
      $this->dspaceTargetCollectionHandle = file_get_contents($this->importPath . '/' . self::ISDSBR_TARGET_COLLECTION_FILENAME);
      $this->importZipFileName = "isdsbr_{$this->importTimeStamp}.zip";
      $this->importZipFilePath = self::IMPORT_ZIP_PATH . "/{$this->importZipFileName}";
      $this->importMapFileName = 'dspace_import_map-' . $this->importTimeStamp . "-$import_slug.txt";
      $this->importLocalMapPath = getcwd() . '/' . self::IMPORT_LOCAL_MAP_PATH;
      $this->initImport();
      $this->validateImportFolder();
      $this->archiveImportFolder();
      $this->copyArchiveToContainer();
      $this->importContainerArchive();
      $this->executeFilterMedia();
      $this->copyMapFile();
    }

  }

  /**
   * Initializes the import's parameters and path.
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

  /**
   * Validates if the import folder contains expected data.
   *
   * @throws \Exception
   */
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

  /**
   * Archives the import folder's data into a single file.
   */
  private function archiveImportFolder() {
    $this->io()->title('Archiving Files Prior to Transfer');
    $cmd = "cd {$this->importPath}; zip -r {$this->importZipFilePath} *";
    $this->say($cmd);
    passthru($cmd);
  }

  /**
   * Copies the import's archive file to the k8s container.
   */
  private function copyArchiveToContainer() {
    $this->io()->title('Copying Zip file to container');
    $cmd = "kubectl --v=6 cp {$this->importZipFilePath} {$this->dspacePodId}:{$this->importZipFilePath} --namespace={$this->dspacePodNamespace}";
    $this->say($cmd);
    passthru($cmd);
  }

  /**
   * Imports the import archive file into DSpace in the k8s pod.
   */
  private function importContainerArchive() {
    $this->io()->title('Importing Archive Format');
    $dspace_bin = self::DSPACE_BIN_PATH;
    $mapfile = '/tmp/' . $this->importMapFileName;
    $cmd = "kubectl exec -t {$this->dspacePodId} --namespace={$this->dspacePodNamespace} -- $dspace_bin import --add --collection={$this->dspaceTargetCollectionHandle} --eperson=" . self::DSPACE_ADMIN_USER . " --source=" . self::IMPORT_ZIP_PATH . " --zip={$this->importZipFileName} --mapfile=$mapfile";
    $this->say($cmd);
    passthru($cmd);
  }

  /**
   * Executes the filter-media command in the k8s pod on the target collection.
   */
  private function executeFilterMedia() {
    $this->io()->title('Executing dspace filter-media on collection');
    $dspace_bin = self::DSPACE_BIN_PATH;
    $cmd = "kubectl exec -t {$this->dspacePodId} --namespace={$this->dspacePodNamespace} -- $dspace_bin filter-media -i {$this->dspaceTargetCollectionHandle}";
    $this->say($cmd);
    passthru($cmd);
  }

  /**
   * Copies a previously generated map file to the k8s pod.
   */
  private function copyMapFile() {
    $this->io()->title('Copying Map File To Local');
    $cmd = "kubectl cp {$this->dspacePodId}:/tmp/{$this->importMapFileName} {$this->importLocalMapPath}/{$this->importMapFileName} --namespace={$this->dspacePodNamespace}";
    $this->say($cmd);
    passthru($cmd);
  }

  /**
   * Reverts a previously imported set of data using a local mapfile.
   *
   * @param string $map_filepath
   *   The filepath of the mapfile to revert.
   * @param string $kubectl_pod_id
   *   The DSpace pod ID to target
   * @param string $kubectl_deployment_env
   *   The DSpace deployment environment
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option yes
   *   Assume a yes response for all prompts.
   *
   * @command isdsbr:import:revert
   */
  public function dspaceRevertImport($map_filepath, $kubectl_pod_id, $kubectl_deployment_env, $options = ['yes' => FALSE]) {
    $this->dspacePodNamespace = $kubectl_deployment_env;
    $this->dspacePodId = $kubectl_pod_id;
    $this->importMapFileName = basename($map_filepath);
    $this->importLocalMapPath = $map_filepath;
    $this->copyMapFileToContainer();
    $this->revertImport();
  }

  /**
   * Copies a local map file to the k8s container.
   */
  private function copyMapFileToContainer() {
    $this->io()->title('Copying Map File To Local');
    $cmd = "kubectl cp {$this->importLocalMapPath} {$this->dspacePodId}:/tmp/{$this->importMapFileName} --namespace={$this->dspacePodNamespace}";
    $this->say($cmd);
    passthru($cmd);
  }

  /**
   * Reverts a previously imported set of content.
   */
  private function revertImport() {
    $this->io()->title('Reverting Import');
    $dspace_bin = self::DSPACE_BIN_PATH;
    $cmd = "kubectl exec -t {$this->dspacePodId} --namespace={$this->dspacePodNamespace} -- $dspace_bin import --eperson=" . self::DSPACE_ADMIN_USER . " -d -m /tmp/{$this->importMapFileName}";
    $this->say($cmd);
    passthru($cmd);
  }

}
