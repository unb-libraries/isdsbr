<?php

namespace UnbLibraries\IslandoraDspaceBridge\Robo\Commands;

/**
 * Provides commands to revert a previous import from DSpace.
 */
class DspaceImportRevertCommand extends IslandoraDspaceBridgeCommand {

  const DSPACE_ADMIN_USER = 'jsanford@unb.ca';
  const DSPACE_BIN_PATH = '/dspace/bin/dspace';

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
    $this->io()->newLine();
    $this->revertImport();
  }

  /**
   * Copies a local map file to the k8s container.
   */
  private function copyMapFileToContainer() {
    $this->io()->title('Copying Map File To k8s pod...');
    $cmd = "kubectl cp {$this->importLocalMapPath} {$this->dspacePodId}:/tmp/{$this->importMapFileName} --namespace={$this->dspacePodNamespace}";
    $this->say($cmd);
    passthru($cmd);
  }

  /**
   * Reverts a previously imported set of content.
   */
  private function revertImport() {
    $this->io()->title('Reverting Import...');
    $dspace_bin = self::DSPACE_BIN_PATH;
    $cmd = "kubectl exec -t {$this->dspacePodId} --namespace={$this->dspacePodNamespace} -- $dspace_bin import --eperson=" . self::DSPACE_ADMIN_USER . " -d -m /tmp/{$this->importMapFileName}";
    $this->say($cmd);
    passthru($cmd);
  }

}
