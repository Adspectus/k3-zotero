<?php
/**
 * This file contains the ZoteroItem class for the Zotero plugin.
 */

namespace Adspectus\Zotero;

/**
 * The ZoteroItem class is a class to provide details for a Zotero item.
 * All properties will be derived from the content returned by the API
 */
class ZoteroItem {

  /**
   * The Zotero key of an item
   * 
   * @var string
   */
  private $key;

  /**
   * The bibliographic content of an item
   * 
   * @var string
   */
  private $bib = '';

  /**
   * The citation content of an item
   * 
   * @var string
   */
  private $citation = '';

  /**
   * The meta content of an item
   * 
   * @var object
   */
  private $meta = [];

  /**
   * The data content of an item
   * 
   * @var object
   */
  private $data = [];

  /**
   * The additional export formats of an item
   * 
   * @var array
   */
  private $exportFormats = [];

  /**
   * The constructor will set the key of the item.
   * 
   * @param string $key
   * @param int $version
   */
  public function __construct(string $key, int $version) {
    $this->key = $key;
    $this->version = $version;
  }

  /**
   * Set the formatted reference.
   * 
   * @param string $bib
   * @return void
   */
  public function setBib(string $bib): void {
    $this->bib = $bib;
  }

  /**
   * Get the formatted reference.
   * 
   * @return string $this->bib
   */
  public function getBib(): string {
    return $this->bib;
  }

  /**
   * Set the formatted citation.
   * 
   * @param string $citation
   * @return void
   */
  public function setCitation(string $citation): void {
    $this->citation = $citation;
  }

  /**
   * Get the formatted citation.
   * 
   * @return string $this->citation
   */
  public function getCitation(): string {
    return $this->citation;
  }

  /**
   * Set the meta array
   * 
   * @param object $meta
   * @return void
   */
  public function setMeta(object $meta): void {
    $this->meta = $meta;
  }

  /**
   * Get the meta array.
   * 
   * @return object $this->meta
   */
  public function getMeta(): object {
    return $this->meta;
  }

  /**
   * Set the data array
   * 
   * @param object $data
   * @return void
   */
  public function setData(object $data): void {
    $this->data = $data;
  }

  /**
   * Get the data array.
   * 
   * @return object $this->data
   */
  public function getData(): object {
    return $this->data;
  }

  /**
   * Set the additional export formats.
   * 
   * @param string $format
   * @param string $exportFormat
   * @return void
   */
  public function SetExportFormats (string $format, string $exportFormat): void {
    $this->exportFormats[$format] = $exportFormat;
  }

  /**
   * Get the additional export formats.
   * 
   * @return array $this->exportFormat
   */
  public function getExportFormats(): array {
    return $this->exportFormats;
  }

  /**
   * Get a specific export format.
   * 
   * @param string $format
   * @return string $exportFormat
   */
  public function getExportFormat(string $format): string {
    return $this->exportFormats[$format];
  }

  /**
   * Get the version
   * 
   * @return int $version
   */
  public function getVersion(): int {
    return $this->version;
  }

  /**
   * Get the num of children
   * 
   * @return int $numOfChildren
   */
  public function getNumChildren(): int {
    return $this->meta->numChildren;
  }
}
