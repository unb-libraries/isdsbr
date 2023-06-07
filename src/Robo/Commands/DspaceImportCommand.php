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
   * The DSpace import collection slug.
   *
   * @var string
   */
  protected $importCollectionSlug;

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
   * Imports one or many objects from a simple archive format tree to DSpace.
   *
   * @param string $path
   *   The path to the Simple Archive Format tree.
   * @param string $kubectl_pod_id
   *   The DSpace pod ID to target
   * @param string $kubectl_deployment_env
   *   The DSpace deployment environment
   *
   * @throws \Exception
   *
   * @command isdsbr:import
   * @TODO Clean this nonsense up.
   */
  public function dspaceImportTree($path, $kubectl_pod_id, $kubectl_deployment_env) {
    $this->dspacePodNamespace = $kubectl_deployment_env;
    $this->dspacePodId = $kubectl_pod_id;
    $this->importTimeStamp = time();

    $dir_finder = new Finder();
    $dir_finder->directories()->in($path)->depth(0);
    foreach ($dir_finder as $import_dir) {
      $this->dspaceImportItem($import_dir);
    }
  }

  /**
   * Imports a single Simple Archive Format item from a given path into k8s.
   *
   * @param \Symfony\Component\Finder\SplFileInfo $import_dir
   *   The path to the Simple Archive Format tree.
   *
   * @throws \Exception
   */
  protected function dspaceImportItem($import_dir) {
    $this->importPath = $import_dir->getPathname();
    $this->importCollectionSlug = $import_dir->getBasename();
    $this->initImport();
    $this->validateImportFolder();
    $this->archiveImportFolder();
    $this->copyArchiveToContainer();
    $this->importContainerArchive();
    $this->executeFilterMedia();
    $this->copyMapFile();
  }

  /**
   * Initializes the import's parameters and paths.
   *
   * @throws \Exception
   */
  protected function initImport() {
    if ($this->importPath == NULL || !is_writable($this->importPath)) {
      throw new Exception(
        sprintf(
          'The specified import path, %s is not writable',
          $this->importPath
        )
      );
    }
    $import_slug = basename($this->importPath);
    $this->dspaceTargetCollectionHandle = file_get_contents($this->importPath . '/' . self::ISDSBR_TARGET_COLLECTION_FILENAME);
    $this->importZipFileName = "isdsbr_{$this->importTimeStamp}_{$this->importCollectionSlug}.zip";
    $this->importZipFilePath = self::IMPORT_ZIP_PATH . "/$this->importZipFileName";
    $this->importMapFileName = 'dspace_import_map-' . $this->importTimeStamp . "-$import_slug.txt";
    $this->importLocalMapPath = getcwd() . '/' . self::IMPORT_LOCAL_MAP_PATH;
  }

  /**
   * Validates if the import folder contains expected data.
   *
   * @throws \Exception
   */
  protected function validateImportFolder() {
    $finder = new Finder();
    $finder->files()->in($this->importPath)->name(self::EXPORT_DIR_IDENTIFIER);
    if ($finder->hasResults()) {
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
  protected function archiveImportFolder() {
    $this->addLogTitle('Archiving Files Prior to Transfer');
    $cmd = "cd $this->importPath; zip -r $this->importZipFilePath *";
    $this->say($cmd);
    passthru($cmd);
  }

  /**
   * Copies the import's archive file to the k8s container.
   */
  protected function copyArchiveToContainer() {
    $this->addLogTitle('Copying Zip file to container');
    $cmd = sprintf(
      'kubectl --v=6 cp %s %s:%s --namespace=%s',
      $this->importZipFilePath,
      $this->dspacePodId,
      $this->importZipFilePath,
      $this->dspacePodNamespace
    );
    $this->say($cmd);
    passthru($cmd);
  }

  /**
   * Imports the import archive file into DSpace in the k8s pod.
   */
  protected function importContainerArchive() {
    $this->addLogTitle('Importing Archive Format');
    $cmd = sprintf(
      'kubectl exec -t %s --namespace=%s -- %s import --add --collection=%s --eperson=%s --source=%s --zip=%s --mapfile=%s',
      $this->dspacePodId,
      $this->dspacePodNamespace,
      self::DSPACE_BIN_PATH,
      trim($this->dspaceTargetCollectionHandle),
      self::DSPACE_ADMIN_USER,
      self::IMPORT_ZIP_PATH,
      $this->importZipFileName,
      '/tmp/' . $this->importMapFileName
    );
    $this->say($cmd);
    passthru($cmd);
  }

  /**
   * Executes the filter-media command in the k8s pod on the target collection.
   */
  protected function executeFilterMedia() {
    $this->addLogTitle('Executing dspace filter-media on collection');
    $cmd = sprintf(
      'kubectl exec -t %s --namespace=%s -- %s filter-media -i %s',
      $this->dspacePodId,
      $this->dspacePodNamespace,
      self::DSPACE_BIN_PATH,
      trim($this->dspaceTargetCollectionHandle)
    );
    $this->say($cmd);
    passthru($cmd);
  }

  /**
   * Copies a previously generated map file to the k8s pod.
   */
  protected function copyMapFile() {
    $this->addLogTitle('Copying Map File To Local');
    $cmd = sprintf(
      'kubectl cp %s:/tmp/%s %s/%s --namespace=%s',
      $this->dspacePodId,
      $this->importMapFileName,
      $this->importLocalMapPath,
      $this->importMapFileName,
      $this->dspacePodNamespace
    );
    $this->say($cmd);
    passthru($cmd);
  }

}
