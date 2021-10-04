<?php

namespace UnbLibraries\IslandoraDspaceBridge\Robo\Commands;

use DOMDocument;
use Exception;
use Robo\Robo;
use Robo\Tasks;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Finder\Finder;
use UnbLibraries\IslandoraDspaceBridge\AuthorTransformTrait;
use UnbLibraries\IslandoraDspaceBridge\DateTransformTrait;
use UnbLibraries\IslandoraDspaceBridge\DepartmentGrantorTransformTrait;
use UnbLibraries\IslandoraDspaceBridge\ElementTransformTrait;
use UnbLibraries\IslandoraDspaceBridge\LanguageTransformTrait;
use UnbLibraries\IslandoraDspaceBridge\SubjectTransformTrait;
use UnbLibraries\IslandoraDspaceBridge\ThesisAdvisorTransformTrait;
use UnbLibraries\IslandoraDspaceBridge\ThesisTypeTransformTrait;

/**
 * Provides commands to convert Islandora exports into Simple Archive Format.
 */
class IslandoraDspaceCrosswalkCommand extends Tasks {

  use AuthorTransformTrait;
  use DateTransformTrait;
  use DepartmentGrantorTransformTrait;
  use ElementTransformTrait;
  use LanguageTransformTrait;
  use SubjectTransformTrait;
  use ThesisAdvisorTransformTrait;
  use ThesisTypeTransformTrait;

  const EXPORT_DIR_IDENTIFIER = 'MODS.0.xml';

  /**
   * The output file configuration.
   *
   * @var object[]
   */
  protected $files = [];

  /**
   * The MODS->DC field mapping definitions.
   *
   * @var string[]
   */
  protected $mappings = [];

  /**
   * The selected MODS->DC mapping style for this conversion.
   *
   * @var string
   */
  protected $mappingStyle = NULL;

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
   * The path to the current MODS XML file being converted.
   *
   * @var string
   */
  protected $sourceItemPath = NULL;

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
   * Convert an Islandora export to DSpace Simple Import Format.
   *
   * @param string $source_path
   *   The path to import the Islandora items from.
   * @param string $target_path
   *   The path to export the Simple Import Format items to.
   * @param string $style
   *   The MODS->DC mapping style to use when converting MODS to Dublin Core.
   *
   * @option yes
   *   Assume a yes response for all prompts.
   *
   * @throws \Exception
   *
   * @command isdsbr:convert
   */
  public function islandoraExportConvertToDspaceImport($source_path, $target_path, $style, $options = ['yes' => FALSE]) {
    $this->sourcePath = $source_path;
    $this->targetPath = $target_path;
    $this->setupFiles();
    $this->setupStyle($style);
    $this->initOperations();
    $this->setUpOperations();
    $this->generateDspaceImports();
  }

  private function setupFiles() {
    $this->files = Robo::Config()->get('isdsbr.files');
    if (empty($this->files['dublin_core'])) {
      throw new Exception(
        'No dublin core filespec found in config.'
      );
    }
  }

  private function setupStyle($style) {
    $this->mappings = Robo::Config()->get('isdsbr.mappings');
    if (empty($this->mappings[$style])) {
      throw new Exception(
        sprintf(
          'Invalid style specified, valid styles are: %s',
          implode(', ', array_keys($this->mappings))
        )
      );
    }
    $this->mappingStyle = $this->mappings[$style];
  }

  private function setTargetItemTargetPath() {
    $padded_counter_string = str_pad($this->targetItemCounter,4,'0',STR_PAD_LEFT);
    $this->targetItemTargetPath = $this->targetPath . "/item_$padded_counter_string";
    mkdir($this->targetItemTargetPath, 0755);
  }

  private function generateDspaceImports() {
    $progress_bar = new ProgressBar($this->output, count($this->operations));
    $progress_bar->setFormat('Converting Objects : %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% Memory Use');
    $progress_bar->start();

    foreach ($this->operations as $operation) {
      $this->sourceItemPath = $operation;
      $this->setTargetItemTargetPath();
      $this->setUpTargetItems();

      foreach ($this->mappingStyle['elements'] as $map_element) {
        $this->setTargetItemElements($map_element);
        // If there aren't any actual values, do not create elements.
        if (!empty($this->targetItemValues)) {
          $this->createDCItems($map_element);
        }
      }
      $this->writeTargetItemFiles();
      $this->writeItemFiles();
      $progress_bar->advance();
      $this->targetItemCounter++;
    }
    $progress_bar->finish();
  }

  private function writeItemFiles() {
    $this->targetItemContents = [];
    $this->writePDFFile();
    $this->writeContentsFile();
  }

  private function writePDFFile() {
    $this->copyItemFile('PDF.0.pdf', 'item.pdf', "\tprimary:true");
  }

  private function writeContentsFile() {
    if (!empty($this->targetItemContents)) {
      $contents_filename = "{$this->targetItemTargetPath}/contents";
      file_put_contents($contents_filename, implode("\n", $this->targetItemContents));
    }
  }

  private function copyItemFile($source_name, $target_name, $identifier = NULL) {
    if (file_exists($this->sourceItemPath . "/$source_name")) {
      copy (
        $this->sourceItemPath . "/$source_name",
        $this->targetItemTargetPath . "/$target_name"
      );
      $this->targetItemContents[] = $target_name.$identifier;
    }
  }

  private function setUpTargetItems() {
    $this->sourceItemCrawler = new Crawler(file_get_contents($this->sourceItemPath . '/MODS.0.xml'));
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

  private function writeTargetItemFiles() {
    foreach ($this->files as $metadata_id => $metadata_file) {
      $this->files[$metadata_id]['xml']->formatOutput = TRUE;
      $output_file = $this->targetItemTargetPath . '/' . $metadata_file['filename'];
      file_put_contents($output_file, $this->files[$metadata_id]['xml']->saveXML());
    }
  }

  /**
   * @param $map_element
   */
  private function setTargetItemElements($map_element) {
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
   * @param $map_item
   */
  private function createDCItems(&$map_item) {
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
   * @param $parent_node
   * @param $name
   * @param $attrs
   *
   * @return mixed
   */
  private function createGetElement(&$parent_node, $name, $attrs, $file_id) {
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

  function getDoc($s) {
    $doc = new DOMDocument;
    $doc->loadxml($s);
    return $doc;
  }

  function getStylesheetData() {
    return <<< eox
<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="iso-8859-1" indent="yes"/>
<xsl:strip-space elements="*" />
<xsl:template match="@* | node()">
    <xsl:copy>
        <xsl:copy-of select="@*"/>
        <xsl:apply-templates select="node()">
            <xsl:sort select="name()"/>
        </xsl:apply-templates>
    </xsl:copy>
</xsl:template>
</xsl:stylesheet>   
eox;
  }

}
