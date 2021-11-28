<?php
/**
 * This file contains the ZoteroAPI class for the Zotero plugin.
 */

namespace Adspectus\Zotero;

load([
  'Adspectus\\Zotero\\ZoteroItem' => 'ZoteroItem.php',
], __DIR__);

use Kirby\Http\Remote;
use Kirby\Cache\Cache;

/**
 * The ZoteroAPI class handles the remote API call.
 */
class ZoteroAPI {

  /**
   * These constants are used as flags for the debug() method to control the amount of output.
   */
  public const DEBUG_OPTIONS      = 0b0000001;
  public const DEBUG_REQUEST      = 0b0000010;
  public const DEBUG_HEADER       = 0b0000100;
  public const DEBUG_CONTENT      = 0b0001000;
  public const DEBUG_DECODED      = 0b0010000;
  public const DEBUG_ALL          = 0b0011111;

  /**
   * The base URL of the Zotero API.
   * 
   * @var string
   */
  private $apiUrl = 'https://api.zotero.org/';

  /**
   * The Zotero API version.
   * 
   * @var int
   */
  private $apiVersion = 3;

  /**
   * The user API key.
   *
   * @var string
   */
  private $apiKey;

  /**
   * The Zotero API request path.
   *
   * @var string|null
   */
  private $path = null;

  /**
   * The returned data format.
   * 
   * @var string
   */
  private $format = 'json';

  /**
   * The parameters for format.
   * 
   * @var string
   */
  private $include = 'bib,citation,data';

  /**
   * The additional export formats.
   * 
   * @var string
   */
  private $exportFormat = 'bibtex,biblatex';

  /**
   * The bibiographic style
   * 
   * @var string 
   */
  private $style = 'din-1505-2';

  /**
   * The sort field
   * 
   * @var string 
   */
  private $sort = 'creator';

  /**
   * The sort order field
   * 
   * @var string
   */
  private $order = '';

  /**
   * The locale
   * 
   * @var string 
   */
  private $locale;

  /**
   * The itemTypes to retrieve
   * 
   * @var string
   */
  private $itemType = '';

  /**
   * The limit
   * 
   * @var int
   */
  private $limit = 100;

  /**
   * The start
   * 
   * @var int
   */
  private $start = 0;

  /**
   * The key of an item
   * 
   * @var string
   */
  private $itemKey;

  /**
   * The key of a collection
   * 
   * @var string
   */
  private $collectionKey;

  /**
   * The tags
   * 
   * @var string
   */
  private $tags;

  /**
   * @var Cache A file cache for caching the feeds.
   */
  private $cache;

  /**
   * @var array Contains only the header as returned by the method headers() in Kirby\Http\Remote.
   */
  private $header = null;

  /**
   * @var string Contains only the content as returned by the method content() in Kirby\Http\Remote.
   */
  private $content = null;

  /**
   * @var array Contains the decoded content.
   */
  private $decodedContent;

  /**
   * @var bool Indicates if a feed comes from the Cache or not.
   */
  private $fromCache = null;

  /**
   * @var array Contains the error messages if an error occured.
   */
  private $error;

  /**
   * Creates a new ZoteroAPI object.
   */
  public function __construct() {

    /**
     * The cache is enabled if the global option is set to true.
     */
    if (kirby()->option('adspectus.zotero.cache') === true) {
      $this->cache = kirby()->cache('adspectus.zotero');
    }
  }

  /**
   * Set all options
   * 
   * @param array $options
   * @return $this
   */
  public function setOptions(array $options): object {
    $this->setAPIKey($options['apiKey']);
    $this->setUser($options['userID']);
#    $this->setGroup($options['groupID']);
    $this->setFormat($options['format']);
    $this->setInclude($options['include']);
    $this->setExportFormat($options['exportFormat']);
    $this->setStyle($options['style']);
    $this->setSort($options['sort']);
    $this->setItemType($options['itemType']);
    $this->setLimit($options['limit']);
    $this->setStart($options['start']);
    return $this;
  }

  /**
   * Set the API key.
   * 
   * @param string $apiKey
   * @return $this
   */
  public function setAPIKey($apiKey): object {
    $this->apiKey = $apiKey;
    return $this;
  }

    /**
   * Prepare the path to call a user resource.
   *
   * @param int $userId
   * @return $this
   */
  public function setUser($userId): object {
    $this->path = 'users/' . $userId;
    return $this;
  }

  /**
   * Prepare the path to call a group resource.
   *
   * @param int $groupId
   * @return $this
   */
  public function setGroup($groupId): object {
    $this->path = 'groups/' . $groupId;
    return $this;
  }

  /**
   * Set the requested format.
   *
   * @param string $format
   * @return $this
   */
  public function setFormat($format): object {
    $this->format = $format;
    return $this;
  }

  /**
   * Set the requested include.
   *
   * @param string $include
   * @return $this
   */
  public function setInclude($include): object {
    $this->include = $include;
    return $this;
  }

  /**
   * Set the requested additional export formats.
   *
   * @param string $exportFormat
   * @return $this
   */
  public function setExportFormat($exportFormat): object {
    $this->exportFormat = $exportFormat;
    return $this;
  }

  /**
   * Set the requested style.
   *
   * @param string $style
   * @return $this
   */
  public function setStyle($style): object {
    $this->style = $style;
    return $this;
  }

  /**
   * Set the sort field.
   *
   * @param string $sort
   * @return $this
   */
  public function setSort($sort): object {
    $this->sort = $sort;
    return $this;
  }

  /**
   * Set the sort order.
   *
   * @param string $order
   * @return $this
   */
  public function setOrder($order): object {
    $this->order = $order;
    return $this;
  }

  /**
   * Set the locale.
   *
   * @param string $locale
   * @return $this
   */
  public function setLocale($locale): object {
    $this->locale = $locale;
    return $this;
  }

  /**
   * Set the itemTypes
   * 
   * @param string $itemType
   * @return $this
   */
  public function setItemType(string $itemType): object {
    $this->itemType = $itemType;
    return $this;
  }

  /**
   * Set the limit
   * 
   * @param int $limit
   * @return $this
   */
  public function setLimit(int $limit): object {
    $this->limit = $limit;
    return $this;
  }

  /**
   * Set the start
   * 
   * @param int $start
   * @return $this
   */
  public function setStart(int $start): object {
    $this->start = $start;
    return $this;
  }

  /**
   * Set the itemKey
   * 
   * @param string $itemKey
   * @return $this
   */
  public function setItemKey(string $itemKey): object {
    $this->itemKey = $itemKey;
    return $this;
  }

  /**
   * Set the collectionKey
   * 
   * @param string $collectionKey
   * @return $this
   */
  public function setCollectionKey(string $collectionKey): object {
    $this->collectionKey = $collectionKey;
    return $this;
  }

  /**
   * Set the tags
   * 
   * @param string $tags
   * @return $this
   */

  public function setTags(string $tags): object {
    $this->tags = $tags;
    return $this;
  }

  /**
   * Set the API request path depending on the requested bibliography type.
   * 
   * @param string $bibType
   * @return $this
   */
  public function setPath(string $bibType): object {
    switch ($bibType) {
      case 'all': $this->path .= '/items';
      break;
      case 'mypub': $this->path .= '/publications/items';
      break;
      case 'item': $this->path .= '/items/' . $this->itemKey;
      break;
      case 'collection': $this->path .= '/collections/' . $this->collectionKey . '/items';
      break;
      default: $this->path .= '/items';
    }
    return $this;
  }

  /**
   * Set the API request path directly.
   * 
   * @param string $path
   * @return $this
   */
  public function setRawPath(string $path): object {
    $this->path .= $path;
    return $this;
  }

  /**
   * @return object
   */
  public function decodeContent(): object {
    if ($this->format === 'json') {
      $content = json_decode($this->content, null);
      if (is_null($content)) {
        $this->error[] = "Error: In " . __METHOD__ . " at line " . __LINE__ . ": could not decode JSON content.";
      }
    }
#    return is_array($content) ? $content : [ $content ];
    $this->decodedContent = is_array($content) ? $content : [ $content ];
    return $this;
  }

  public function getHeader(): array {
    return $this->header;
  }

  public function getContent(): string {
    return $this->content;
  }

  public function fromCache(): bool {
    return $this->fromCache;
  }

  public function request(): object {
#    $exportFormat = implode(',',$this->exportFormat);
    $this->addQueryString2Path([
      'format' => $this->format,
      'include' => $this->include . ($this->exportFormat === '' ? '' : ',' . $this->exportFormat),
      'style' => $this->style,
      'sort' => $this->sort,
      'locale' => $this->locale,
      'itemType' => $this->itemType,
      'limit' => $this->limit,
      'start' => $this->start,
      'tag' => str_replace(',',' || ',$this->tags),
    ]);
    $url = $this->apiUrl . $this->path;

    /**
     * The cached content will only be used, if it has a Last-Modified-Version header.
     * The Last-Modified-Version value will be added to the urlOptions.
     */
    $cachedRequest = [];
    if (is_null($this->cache) === false) {
      if ($cachedRequest = $this->cache->get(md5($url))) {
        if (isset($cachedRequest['header']['Last-Modified-Version'])) {
          $urlOptions['headers']['If-Modified-Since-Version'] = $cachedRequest['header']['Last-Modified-Version'];
        }
      }
    }

    /**
     * The request is made by means of the Kirby\Http\Remote class.
     */

    $urlOptions['headers']['Zotero-API-Version'] = $this->apiVersion;
    $urlOptions['headers']['Zotero-API-Key'] = $this->apiKey;

    $request = Remote::request($url,$urlOptions);
    $this->request = $request;

    /**
     * If the request returns HTTP code 200 (OK), the header and the content of the request will be saved
     * and stored in the cache.
     */
    if ($request->code() === 200) {
      $this->header = $request->headers();
      $this->content = $request->content();
      $this->fromCache = false;
      if (is_null($this->cache) === false) {
        $this->cache->set(md5($url),['header' => $this->header,'content' => $this->content]);
      }
    }
    /**
     * If the request returns HTTP code 304 (Not modified), the header and the content will be taken from
     * the cache.
     */
    elseif ($request->code() === 304) {
      $this->header = $cachedRequest['header'];
      $this->content = $cachedRequest['content'];
      $this->fromCache = true;
    }
    /**
     * All other HTTP return codes will also be reported as an error.
     */
    else {
      $this->error[] = "Error: In " . __METHOD__ . " at line " . __LINE__ . ": $url returns HTTP status code " . $request->code() . ".";
    }

#    $this->decodedContent = $this->decodeContent();
    return $this;
  }

  public function getItems(): array {
    $includes = explode(',',$this->include);
    foreach ($this->decodedContent as $content) {
      $item = new ZoteroItem($content->key,$content->version);
      $item->setMeta($content->meta);
      if (in_array('bib',$includes)) {
        $item->setBib($content->bib);
      }
      if (in_array('citation',$includes)) {
        $item->setCitation($content->citation);
      }
      if (in_array('data',$includes)) {
        $item->setData($content->data);
      }
      foreach (explode(',',$this->exportFormat) as $format) {
        if (in_array($format,$includes)) {
          $item->setExportFormats($format,$content->$format);
        }
      }
      $items[$content->key] = $item;
    }
    return $items ?? [];
  }

  public function debug(int $flags = self::DEBUG_ALL): array {
    $debug = [];

    if ($flags & self::DEBUG_OPTIONS) {
      $debug['options'] = [
        'apiUrl'      => $this->apiUrl,
        'apiVersion'  => $this->apiVersion,
        'apiKey'      => $this->apiKey,
        'path'        => $this->path,
        'fromCache'   => $this->fromCache,
        'error'       => $this->error,
      ];
    }
    if ($flags & self::DEBUG_REQUEST) {
      $debug['request']  = $this->request;
    }
    if ($flags & self::DEBUG_HEADER) {
      $debug['header']  = $this->header;
    }
    if ($flags & self::DEBUG_CONTENT) {
      $debug['content']  = $this->content;
    }
    if ($flags & self::DEBUG_DECODED) {
      $debug['decodedContent'] = $this->decodedContent;
    }

    return $debug;
  }

  /**
   * Append query strings to the path.
   *
   * @param array $queries
   */
  private function addQueryString2Path(array $queries): void {
    $path = $this->path;
    foreach ($queries as $key => $value) {
        $separator = (strpos($path, '?') !== false) ? '&' : '?';
        $path .= $separator . $key . '=' . urlencode($value);
    }
    $this->path = $path;
  }
}
