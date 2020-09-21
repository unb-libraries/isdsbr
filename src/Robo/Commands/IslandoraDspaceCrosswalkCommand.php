<?php

namespace UnbLibraries\IslandoraDspaceBridge\Robo\Commands;

use DOMDocument;
use Exception;
use Robo\Robo;
use Robo\Tasks;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Finder\Finder;

/**
 * Provides commands to convert Islandora exports into Simple Archive Format.
 */
class IslandoraDspaceCrosswalkCommand extends Tasks {

  const EXPORT_DIR_IDENTIFIER = 'MODS.0.xml';

  /**
   * @var array
   */
  protected $mappings = [];

  /**
   * @var array
   */
  protected $mappingStyle = NULL;

  /**
   * @var array
   */
  protected $operations = [];

  /**
   * @var string
   */
  protected $sourcePath = NULL;

  /**
   * @var string
   */
  protected $targetPath = NULL;

  protected $targetCollections = NULL;

  protected $importItemPath = NULL;
  protected $importItemCrawler = NULL;

  protected $exportItemValues = NULL;
  protected $exportItemTargetPath = NULL;
  protected $exportItemCounter = 0;
  protected $exportItemXml = NULL;
  protected $exportItemDcElement = NULL;
  protected $exportItemContents = [];


  /**
   * Convert an Islandora export to DSpace Simple Import Format.
   *
   * @param string $source_path
   *   The path to export the objects to.
   * @param string $target_path
   *   The path to export the objects to.
   * @param string $style
   *   The mapping style to use when converting MODS to Dublin Core.
   * @param string $target_collections
   *   The target collections for the import. First listed owns item.
   *
   * @option yes
   *   Assume a yes response for all prompts.
   *
   * @throws \Exception
   *
   * @command isdsbr:convert
   */
  public function islandoraExportConvertToDspaceImport($source_path, $target_path, $style, $target_collections, $options = ['yes' => FALSE]) {
    $this->sourcePath = $source_path;
    $this->targetPath = $target_path;
    $this->mappings = Robo::Config()->get('isdsbr.mappings');
    $this->mappingStyle = $this->mappings[$style];
    $this->targetCollections = $target_collections;

    $this->initOperations();
    $this->setUpOperations();
    $this->generateDspaceImports();
  }

  private function setExportItemTargetPath() {
    $padded_counter_string = str_pad($this->exportItemCounter,4,'0',STR_PAD_LEFT);
    $this->exportItemTargetPath = $this->targetPath . "/item_$padded_counter_string";
    mkdir($this->exportItemTargetPath, 0755);
  }

  private function generateDspaceImports() {
    foreach ($this->operations as $operation) {
      $this->importItemPath = $operation;
      $this->setExportItemTargetPath();
      $this->setUpExportItem();

      foreach ($this->mappingStyle['elements'] as $map_element) {
        $this->setExportItemValues($map_element);
        $this->createDCItems($map_element);
      }

      $this->writeExportItemFile();
      $this->writeItemFiles();
      $this->exportItemCounter++;
    }
  }

  private function writeItemFiles() {
    $this->exportItemContents = [];
    $this->writeCollectionFile();
    $this->writePDFFile();
    $this->writeContentsFile();
  }

  private function writePDFFile() {
    $this->copyItemFile('PDF.0.pdf', 'item.pdf', "\tprimary:true");
  }

  private function writeContentsFile() {
    if (!empty($this->exportItemContents)) {
      $contents_filename = "{$this->exportItemTargetPath}/contents";
      file_put_contents($contents_filename, implode("\n", $this->exportItemContents));
    }
  }

  private function copyItemFile($source_name, $target_name, $identifier = NULL) {
    if (file_exists($this->importItemPath . "/$source_name")) {
      copy (
        $this->importItemPath . "/$source_name",
        $this->exportItemTargetPath . "/$target_name"
      );
      $this->exportItemContents[] = $target_name.$identifier;
    }
  }

  private function writeCollectionFile() {
    $output_file = $this->exportItemTargetPath . '/collection';
    file_put_contents($output_file, $this->targetCollections);
  }

  private function setUpExportItem() {
    $this->importItemCrawler = new Crawler(file_get_contents($this->importItemPath . '/MODS.0.xml'));
    $this->exportItemXml = new DomDocument('1.0', 'UTF-8');
    $this->exportItemDcElement = $this->exportItemXml->createElement('dublin_core');
  }

  private function writeExportItemFile() {
    $this->exportItemXml->formatOutput = TRUE;
    $output_file = $this->exportItemTargetPath . '/dublin_core.xml';
    file_put_contents($output_file, $this->exportItemXml->saveXML());
  }

  /**
   * @param $map_element
   */
  private function setExportItemValues($map_element) {
    $this->exportItemValues = [];
    foreach ($map_element['source_paths'] as $source_path) {
      $elements = $this->importItemCrawler->filterXPath($source_path);
      foreach ($elements as $element) {
        $text_content = $element->textContent;
        if (!empty($text_content)) {
          $this->exportItemValues[] = $text_content;
        }
      }
    }
    // Values callback.
    if (!empty($map_element['transform_callback'])) {
      $this->{$map_element['transform_callback']}();
    }
  }

  /**
   * @param $map_item
   */
  private function createDCItems(&$map_item) {
    $prev_node = $this->exportItemDcElement;
    $last_path_key = array_key_last($map_item['target_path']);
    foreach ($map_item['target_path'] as $path_key => $path) {
      if ($path_key == $last_path_key) {


        // If this key already exists, we should add another anyhow.
        foreach ($this->exportItemValues as $value) {
          $new_node = $this->exportItemXml->createElement($path['name']);
          foreach ($path['attributes'] as $attribute_name => $attribute_value) {
            $new_node->setAttribute($attribute_name, $attribute_value);
          }
          $new_node->nodeValue = $value;
          $prev_node->appendChild($new_node);
        }
      }
      else {
        // This is not the value item and may be be an existing node!
        $prev_node = $this->createGetElement($prev_node, $path['name'], $path['attributes']);
      }
    }
    $this->exportItemXml->appendChild($this->exportItemDcElement);
  }

  /**
   * @param $parent_node
   * @param $name
   * @param $attrs
   *
   * @return mixed
   */
  private function createGetElement(&$parent_node, $name, $attrs) {
    $existing = $this->searchChildElementsByName($parent_node, $name, $attrs);
    if (!empty($existing)) {
      $all_attr_match = TRUE;
      foreach ($attrs as $attr_name => $attr_value) {
        $existing_value = $existing->getAttribute($attr_name);
        if ($existing_value != $attr_value) {
          $all_attr_match = FALSE;
        }
      }
      if ($all_attr_match == TRUE) {
        return $existing;
      }
    }

    $new_node = $this->exportItemXml->createElement($name);
    foreach ($attrs as $attribute_name => $attribute_value) {
      $new_node->setAttribute($attribute_name, $attribute_value);
    }
    $parent_node->appendChild($new_node);
    return $new_node;
  }

  /**
   * @param $node
   * @param $name
   * @param $attrs
   *
   * @return mixed|null
   */
  private function searchChildElementsByName($node, $name, $attrs) {
    foreach ($node->childNodes as $child ) {
      if ( $child->nodeName == $name ) {
        return $child;
      }
    }
    return NULL;
  }

  private function initOperations() {
    if ( $this->targetPath == NULL || !is_writable($this->targetPath)) {
      throw new Exception(
        sprintf(
          'The specified target path, %s is not writable',
          $this->targetPath
        )
      );
    }
  }

  private function setUpOperations() {
    $finder = new Finder();
    $finder->files()->in($this->sourcePath)->name(self::EXPORT_DIR_IDENTIFIER);
    foreach ($finder as $file) {
      $this->operations[] = $file->getPath();
    }
  }

  private function transformModsTitle() {
    // $this->exportItemValues
  }

}
