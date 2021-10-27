<?php

namespace UnbLibraries\IslandoraDspaceBridge\Robo\Commands;

use DOMDocument;
use Exception;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Finder\Finder;
use UnbLibraries\IslandoraDspaceBridge\AuthorTransformTrait;
use UnbLibraries\IslandoraDspaceBridge\DateTransformTrait;
use UnbLibraries\IslandoraDspaceBridge\DepartmentGrantorTransformTrait;
use UnbLibraries\IslandoraDspaceBridge\ElementTransformTrait;
use UnbLibraries\IslandoraDspaceBridge\LanguageTransformTrait;
use UnbLibraries\IslandoraDspaceBridge\LiteralUnbPublisherTrait;
use UnbLibraries\IslandoraDspaceBridge\MimeTypeTrait;
use UnbLibraries\IslandoraDspaceBridge\Robo\Commands\IslandoraDspaceBridgeCommand;
use UnbLibraries\IslandoraDspaceBridge\SubjectTransformTrait;
use UnbLibraries\IslandoraDspaceBridge\ThesisAdvisorTransformTrait;
use UnbLibraries\IslandoraDspaceBridge\ThesisTypeTransformTrait;
use UnbLibraries\IslandoraDspaceBridge\TitleTransformTrait;

/**
 * Provides commands to convert Islandora exports into Simple Archive Format.
 */
class IslandoraDspaceCrosswalkCommand extends IslandoraDspaceBridgeCommand {

  use AuthorTransformTrait;
  use DateTransformTrait;
  use DepartmentGrantorTransformTrait;
  use ElementTransformTrait;
  use LanguageTransformTrait;
  use LiteralUnbPublisherTrait;
  use MimeTypeTrait;
  use SubjectTransformTrait;
  use ThesisAdvisorTransformTrait;
  use ThesisTypeTransformTrait;
  use TitleTransformTrait;

  /**
   * The current operation's item source path.
   *
   * @var string
   */
  protected $curOperationItemSourcePath;

  /**
   * The current operation source path.
   *
   * @var string
   */
  protected $curOperationSourcePath;

  /**
   * The output file configuration.
   *
   * @var object[]
   */
  protected $files = [];

  /**
   * The selected MODS->DC mapping style for this conversion.
   *
   * @var string
   */
  protected $mappingStyle = NULL;

  /**
   * The MODS->DC field mapping definitions.
   *
   * @var string[]
   */
  protected $mapStyle;

  /**
   * A list of MODS items to convert into Simple Archive Format.
   *
   * @var array
   */
  protected $operations = [];

  /**
   * The crawler of the current MODS XML item being converted.
   *
   * @var \Symfony\Component\DomCrawler\Crawler
   */
  protected $sourceItemCrawler = NULL;

  /**
   * The path to the current MODS bundle being converted.
   *
   * @var string
   */
  protected $sourcePath = NULL;

  /**
   * The files written by the current MODS bundle being converted.
   *
   * @var string[]
   */
  protected $targetItemContents = [];

  /**
   * The number of MODS items that have been converted to Simple Archive Format.
   *
   * @var int
   */
  protected $targetItemCounter = 0;

  /**
   * The path to the current DC XML being written.
   *
   * @var string
   */
  protected $targetItemTargetPath = NULL;

  /**
   * The elements of the target Item.
   *
   * @var string[]
   */
  protected $targetItemElements = [];

  /**
   * A list of values for the current DC element being converted.
   *
   * @var string[]
   */
  protected $targetItemValues = [];

  /**
   * The path to the current Simple Archive Format being written.
   *
   * @var string
   */
  protected $targetPath = NULL;


  /**
   * Converts an Islandora export to a DSpace Simple Import Format tree.
   *
   * @param string $source_path
   *   The path to import the Islandora items from.
   * @param string $target_path
   *   The path to export the Simple Import Format items to.
   *
   * @command isdsbr:crosswalk
   * @usage isdsbr:crosswalk /tmp/source /tmp/target
   *
   * @throws \Exception
   */
  public function islandoraExportConvertToDspaceImport($source_path, $target_path) {
    $this->sourcePath = $source_path;
    $this->targetPath = $target_path;
    $this->initOperations();
    $this->setUpOperations();
    $this->generateDspaceImports();
  }

  /**
   * Initilializes the target path for conversion operations.
   *
   * @throws \Exception
   */
  protected function initOperations() {
    if ( $this->targetPath == NULL || !is_writable($this->targetPath)) {
      throw new Exception(
        sprintf(
          'The specified target path, %s is not writable',
          $this->targetPath
        )
      );
    }
  }

  /**
   * Sets up the list of operations (source items) to convert.
   */
  protected function setUpOperations() {
    $this->addLogTitle('Discovering Exported Objects');
    $dir_finder = new Finder();
    $dir_finder->in($this->sourcePath)->depth('0')->directories();
    foreach ($dir_finder as $import_dir) {
      $source_dir = $import_dir->getRealPath();
      $this->addLogStrong("Crawling $source_dir...");
      $this->operations[$source_dir] = [];
      $finder = new Finder();
      $finder->files()->in($source_dir)->name(self::ISDSBR_EXPORT_DIR_IDENTIFIER);
      foreach ($finder as $file) {
        $full_object_path = $file->getPath();
        $this->operations[$source_dir][] = $full_object_path;
        $this->addLogNotice("[$source_dir] Adding $full_object_path...");
      }
    }
  }

  /**
   * Generates the DSpace Simple Archive Format (import) from the source.
   *
   * @throws \Exception
   */
  protected function generateDspaceImports() {
    foreach ($this->operations as $this->curOperationSourcePath => $operation_paths) {
      $this->addLogTitle("Converting Exported Objects : $this->curOperationSourcePath");
      $this->initCurImportOperation();
      foreach ($operation_paths as $this->curOperationItemSourcePath) {
        $this->addLogStrong('Converting ' . $this->curOperationItemSourcePath);
        $this->setTargetItemTargetPath();
        $this->convertAppentModsMetadata();
        foreach ($this->mappingStyle['elements'] as $map_element) {
          $this->setTargetItemElements($map_element);
          // If there aren't any actual values, do not create elements.
          if (!empty($this->targetItemValues)) {
            $this->createDCItems($map_element);
          }
        }
        $this->writeTargetItemMetadataFiles();
        $this->writeTargetItemBitstreamFiles();
        $this->targetItemCounter++;
      }
    }
  }

  /**
   * Copies the target collection info to the output path.
   */
  protected function copyTargetCollectionFile() {
    $source_file = $this->curOperationSourcePath . '/' . self::ISDSBR_TARGET_COLLECTION_FILENAME;
    $operation_basename = basename($this->curOperationSourcePath);
    $operation_target_dir = $this->targetPath . "/$operation_basename";
    $output_file = $operation_target_dir . '/' . self::ISDSBR_TARGET_COLLECTION_FILENAME;
    copy($source_file, $output_file);
  }

  /**
   * Initializes and sets up the current import operation.
   *
   * @throws \Exception
   */
  protected function initCurImportOperation() {
    $this->targetItemCounter = 0;
    $this->setupCurOperationStyle();
    $this->setupCurOperationFiles();
    $this->setupCurOperationBaseDir();
    $this->copyTargetCollectionFile();
  }

  /**
   * Reads and sets up the mapping style for the current import set operation.
   *
   * @throws \Exception
   */
  protected function setupCurOperationStyle() {
    $this->setCurOperationMapStyle();
    $map_style_filepath = $this->curOperationSourcePath . '/' . self::ISDSBR_FIELD_MAPPING_FILENAME;
    if (empty($this->mapStyle)) {
      throw new Exception(
        sprintf(
          'Unable to read style read from filepath, %s',
          $map_style_filepath
        )
      );
    }
    $this->mappingStyle = $this->mapStyle['mappings'][file_get_contents($map_style_filepath)];
  }

  /**
   * Sets up the mapping style for the current import set operation.
   */
  protected function setCurOperationMapStyle() {
    $map_style_file = $this->curOperationSourcePath . '/' . self::ISDSBR_FIELD_MAPPING_FILENAME;
    $map_style = file_get_contents($map_style_file);
    $field_map_file = file_get_contents(__DIR__ . "/../../../field_maps/$map_style.yml");
    $field_map = yaml_parse($field_map_file);
    $this->mapStyle = $field_map['isdsbr']['field_maps'][$map_style];
  }

  /**
   * Sets up the mapping files for the current import set operation.
   *
   * @throws \Exception
   */
  protected function setupCurOperationFiles() {
    $this->files = $this->mapStyle['files'];
    if (empty($this->files['dublin_core'])) {
      throw new Exception(
        'No dublin core filespec found in config.'
      );
    }
  }

  /**
   * Sets up the base target directory for the current import operation.
   */
  protected function setupCurOperationBaseDir() {
    $operation_basename = basename($this->curOperationSourcePath);
    $operation_target_dir = $this->targetPath . "/$operation_basename";
    mkdir($operation_target_dir, 0755);
  }

  /**
   * Sets up the target item path for the current item.
   */
  protected function setTargetItemTargetPath() {
    $padded_counter_string = str_pad($this->targetItemCounter,4,'0',STR_PAD_LEFT);
    $operation_basename = basename($this->curOperationSourcePath);
    $this->targetItemTargetPath = $this->targetPath . "/$operation_basename/item_$padded_counter_string";    mkdir($this->targetItemTargetPath, 0755);
  }

  /**
   * Converts the MODS metadata into DC format(s), appends to current item DOM.
   */
  protected function convertAppentModsMetadata() {
    $latest_file = $this->getLatestIslandoraFile('MODS.*.xml');
    $this->addLogNotice("Metadata Source: $latest_file");
    $this->sourceItemCrawler = new Crawler(file_get_contents($latest_file));
    foreach ($this->files as $metadata_id => $metadata_file) {
      $this->files[$metadata_id]['xml'] = new DomDocument($metadata_file['xml-version'], $metadata_file['xml-encoding']);
      $dc_element = $this->files[$metadata_id]['xml']->createElement('dublin_core');
      if (!empty($metadata_file['schema'])) {
        $schema_attribute = $this->files[$metadata_id]['xml']->createAttribute('schema');
        $schema_attribute->value = $metadata_file['schema'];
        $dc_element->appendChild($schema_attribute);
      }
      $this->files[$metadata_id]['dcelement'] = $dc_element;
    }
  }

  /**
   * Determines the 'latest' file from a file mask.
   *
   * @param $file_mask
   *   The file mask to use.
   *
   * @return string
   *   The latest filepath.
   */
  protected function getLatestIslandoraFile($file_mask) {
    $numeric_files = glob($this->curOperationItemSourcePath . "/$file_mask");
    if (!empty($numeric_files)) {
      natsort($numeric_files);
      return end($numeric_files);
    }
    return '';
  }

  /**
   * Maps the MODS elements to the target DC elements.
   *
   * @param \DOMElement $map_element
   *   The DOM element to map from MODS.
   */
  protected function setTargetItemElements($map_element) {
    $this->targetItemElements = [];
    $this->targetItemValues = [];
    foreach ($map_element['source_paths'] as $source_path) {
      $elements = $this->sourceItemCrawler->filterXPath($source_path);
      foreach ($elements as $element) {
        $this->targetItemElements[] = $element;
      }
    }
    // Values callback.
    if (!empty($map_element['transform_callback'])) {
      $this->{$map_element['transform_callback']}();
    }
  }

  /**
   * Creates the DC metadata elements from the MODS source.
   *
   * @param array $map_item
   *   The item to map.
   */
  protected function createDCItems(&$map_item) {
    $prev_node = $this->files[$map_item['target_file']]['dcelement'];
    $last_path_key = array_key_last($map_item['target_path']);

    foreach ($map_item['target_path'] as $path_key => $path) {
      // Make sure we have (at minimum) an empty attrs array for this path.
      $path['attributes'] = $path['attributes'] ?? [];

      if ($path_key == $last_path_key) {
        // This is where the value lives - add new elements each time.
        foreach ($this->targetItemValues as $value) {
          $new_node = $this->files[$map_item['target_file']]['xml']->createElement($path['name']);
          foreach ($path['attributes'] as $attribute_name => $attribute_value) {
            $new_node->setAttribute($attribute_name, $attribute_value);
          }
          $new_node->nodeValue = htmlspecialchars($value);
          $prev_node->appendChild($new_node);
        }
      }
      else {
        // This is not the value item, try to grab an existing element.
        $prev_node = $this->createGetElement($prev_node, $path['name'], $path['attributes'], $map_item['target_file']);
      }
    }
    $this->files[$map_item['target_file']]['xml']->appendChild($this->files[$map_item['target_file']]['dcelement']);
  }

  /**
   * Gets an element matching the parameters if it exists, otherwise creates it.
   *
   * @param \DOMNode $parent_node
   *   The parent node in the DOM to search for the existing element.
   * @param string $name
   *   The name of the element.
   * @param string[] $attrs
   *   The attributes of the element.
   * @param string $file_id
   *   The file ID to target.
   *
   * @return mixed
   *   The element, either found or newly created.
   */
  protected function createGetElement(&$parent_node, $name, $attrs, $file_id) {
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

    $new_node = $this->files[$file_id]['xml']->createElement($name);
    if (!empty($attrs)) {
      foreach ($attrs as $attribute_name => $attribute_value) {
        $new_node->setAttribute($attribute_name, $attribute_value);
      }
    }
    $parent_node->appendChild($new_node);
    return $new_node;
  }

  /**
   * Writes out the current item's target DC metadata file(s).
   */
  protected function writeTargetItemMetadataFiles() {
    foreach ($this->files as $metadata_id => $metadata_file) {
      $this->files[$metadata_id]['xml']->formatOutput = TRUE;
      $output_file = $this->targetItemTargetPath . '/' . $metadata_file['filename'];
      file_put_contents($output_file, $this->files[$metadata_id]['xml']->saveXML());
    }
  }

  /**
   * Writes out the current item's bitstream files into the SAF directory.
   */
  protected function writeTargetItemBitstreamFiles() {
    $this->targetItemContents = [];
    $this->writePDFFile();
    $this->writeMODSFile();
    $this->writeContentsFile();
  }

  /**
   * Writes out the current item's main (PDF) file into the SAF directory.
   */
  protected function writePDFFile() {
    $latest_pdf = $this->getLatestIslandoraFile('PDF.*.pdf');
    if (!empty($latest_pdf)) {
      $latest_pdf_name = basename($latest_pdf);
      $this->copyItemFile($latest_pdf_name, 'item.pdf', "\tprimary:true");
    }
  }

  /**
   * Writes out the current item's MODS file into the SAF directory.
   */
  protected function writeMODSFile() {
    $latest_mods = $this->getLatestIslandoraFile('MODS.*.xml');
    $latest_mods_name = basename($latest_mods);
    $this->copyItemFile($latest_mods_name, 'MODS.xml', "\tbundle:ISLANDORA\tpermissions:-r 'Anonymous'");
  }

  /**
   * Provides a helper method to copy bitstreams from source to target.
   *
   * @param string $source_name
   *   The name of the source file to copy.
   * @param string $target_name
   *   The name of the target file.
   * @param string $identifier
   *   The identifier to use in the SAF contents file.
   */
  protected function copyItemFile($source_name, $target_name, $identifier = NULL) {
    $this->addLogNotice("Copying: $source_name -> $target_name");
    if (file_exists($this->curOperationItemSourcePath . "/$source_name")) {
      copy (
        $this->curOperationItemSourcePath . "/$source_name",
        $this->targetItemTargetPath . "/$target_name"
      );
      $this->targetItemContents[] = $target_name.$identifier;
    }
  }

  /**
   * Writes out the current item's 'contents' file into the SAF directory.
   */
  protected function writeContentsFile() {
    if (!empty($this->targetItemContents)) {
      $contents_filename = "$this->targetItemTargetPath/contents";
      file_put_contents($contents_filename, implode("\n", $this->targetItemContents));
    }
  }

}
