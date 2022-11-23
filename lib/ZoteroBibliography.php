<?php
/**
 * This file registers the Zotero Plugin.
 * 
 * The Zotero plugin provides access to the items in Zotero.
 * 
 * @version    1.0.0
 * @author     Uwe Gehring <uwe@imap.cc
 * @copyright  Uwe Gehring <uwe@imap.cc
 * @license    GNU General Public License v3.0
 * @link       
 */

use Adspectus\Zotero\ZoteroAPI;
use Kirby\Cms\File;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;

if (!function_exists('str_contains')) {
  function str_contains($haystack, $needle) {
      return $needle !== '' && mb_strpos($haystack, $needle) !== false;
  }
}

function deleteBibliography($page,bool $debug = false) {
  foreach ($page->children() as $child) {
    $child->delete();
  }
}

function createBibliography($page,bool $debug = false) {

  if ((PHP_SAPI === 'cli') && ! $debug) {
    echo "\n";
  }

  $debug && formatPrintLn(['underline'],"\nWorking on items...");

  /**
   * Get the items without attachments or notes.
   */
  $zotero = new ZoteroAPI();
  $zotero->setOptions(kirby()->option('adspectus.zotero'));
  $zotero->setLocale(str_replace('_','-',explode('.',kirby()->language()->locale(LC_ALL))[0]))->setInclude('bib,data')->setStyle($page->citationstyle()->toString())->setItemType('-attachment || note')->setLimit(100)->setCollectionKey($page->collectionkey()->toString())->setPath($page->bibtype()->toString());
  $zotero->request()->decodeContent();

  foreach ($zotero->getItems() as $key => $item) {
    $debug && print("Item " . $key);
    if ((PHP_SAPI === 'cli') && ! $debug) {
      echo "+";
    }
    /**
     * First it will be checked, if a child page already exists.
     */
    $childPage = $page->find(Str::slug($key));

    /**
     * If a child page does not exist, it will be created.
     * If it exist, the version number will be compared. If the versions differ,
     * the old page will be deleted and the new will be created.
     */
    if (is_null($childPage)) {
      $debug && formatPrint(['blue']," missing, will be created");
      createAndPublishChild($page,$item,$debug);
    }
    else {
      $debug && formatPrint(['green']," exists");
      $thisVersion = $childPage->version()->toInt();
      if ($item->getVersion() != $thisVersion) {
        $debug && formatPrint(['blue'],", but outdated, will be deleted and recreated");
        $childPage->delete();
        createAndPublishChild($page,$item,$debug);
      }
      else {
        $debug && formatPrint(['green'],", same version, skipping.\n");
      }
    }
  }

  if ((PHP_SAPI === 'cli') && ! $debug) {
    echo "\n";
  }

  $debug && formatPrintLn(['underline'],"\nWorking on Attachments and Notes...");

  /**
   * After working on the items, the attachments and notes will be fetched.
   */
  $zotero = new ZoteroAPI();
  $zotero->setOptions(kirby()->option('adspectus.zotero'));
  $zotero->setLocale(str_replace('_','-',explode('.',kirby()->language()->locale(LC_ALL))[0]))->setItemType('attachment || note')->setLimit(100)->setCollectionKey($page->collectionkey()->toString())->setPath($page->bibtype()->toString());
  $zotero->request()->decodeContent();

  foreach ($zotero->getItems() as $key => $item) {
    if ((PHP_SAPI === 'cli') && ! $debug) {
      echo ".";
    }
    $data = $item->getData();
    $parentSlug = Str::slug($data->parentItem);
    $parentPage = $page->find($parentSlug);

    /**
     * Attachments
     */
    if ($data->itemType === 'attachment') {
      $debug && print("Atmt " . $key);
      /**
       * First it will be checked, if a file already exist. If not, it will
       * be fetched. If it exists, the version number will be compared. If
       * the versions differ, the file will be fetched as well. Otherwise the
       * next file is processed.
       */
      $fileName = F::safeName($data->filename);
      if (F::exists($page->root() . '/' . $parentSlug . '/' . $fileName)) {
        $debug && formatPrint(['green']," exists");
        $thisFile = $parentPage->file($fileName);
        if ($data->version == $thisFile->version()->toString()) {
          $debug && formatPrint(['green'],", same version, skipping.\n");
          continue;
        }
        else {
          $debug && formatPrint(['blue'],", but outdated, will be recreated");
        }
      }
      else {
        $debug && formatPrint(['blue']," missing, will be created");
      }

      $content['caption'] = $data->title;
      $content['version'] = $data->version;
      $content['tags'] = implode(',',array_map(function($element) { return $element->tag; },$data->tags));

      /**
       * A new request is necessary to download the file.
       */
      $zoteroAttachment = new ZoteroAPI();
      $zoteroAttachment->setOptions(kirby()->option('adspectus.zotero'));
      $zoteroAttachment->setRawPath('/items/' . $data->key . '/file/view');
      $zoteroAttachment->request();

      $fileContent = $zoteroAttachment->getContent();

      if (isset($fileContent)) {
        if (F::write($page->root() . '/' . $fileName,$fileContent)) {
          try {
            File::create([
              'blueprint' => ['accept' => true],
              'content' => $content,
              'filename' => $fileName,
              'source' => $page->root() . '/' . $fileName,
              'parent' => $parentPage,
            ]);
          }
          catch (Exception $e) {
            $debug && formatPrintLn(['red'],$e);
          }
          F::remove($page->root() . '/' . $fileName);
          $debug && formatPrint(['green']," as " . $fileName . ", done.\n");
        }
        else {
          $debug && formatPrintLn(['red'],", not writable.");
        }
      }
      else {
        $debug && formatPrintLn(['red'],", no content.");
      }
    }

    /**
     * Notes
     */
    if ($data->itemType === 'note') {
      $debug && print("Note " . $key);
      $note = ['version' => $data->version, 'note' => $data->note];
      $noteFile = $page->root() . '/' . $parentSlug . '/note-' . strtotime($data->dateAdded) . '.json';
      if (F::exists($noteFile)) {
        $debug && formatPrint(['green']," exists");
        $thisNote = json_decode(F::read($noteFile));
        if ($data->version == $thisNote->version) {
          $debug && formatPrintLn(['green'],", same version, skipping.");
          continue;
        }
        else {
          $debug && formatPrint(['blue'],", but outdated, will be recreated");
        }
      }
      else {
        $debug && formatPrint(['blue']," missing, will be created");
      }
      F::write($noteFile,json_encode($note,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
      $debug && formatPrintLn(['green'],", done.");
    }
  }

  if ((PHP_SAPI === 'cli') && ! $debug) {
    echo "\n";
  }

  /**
   * Last, the toggles Deleteitems and Refreshitems will be resetted to false.
   */
  $debug || $page->update(['deleteitems' => false,'refreshitems' => false]);
}

function createAndPublishChild (object $page,object $item,bool $debug = false) {
  $meta = $item->getMeta();
  $data = $item->getData();
  $slug = Str::slug($data->key);
  $lang = $page->translation()->code();

  /**
   * Even though when the german version is requested, the creatorSummary field
   * within the meta section of Zotero, contains the word "and" instead of "und"
   * if the item has multiple creators.
   */
  if (isset($meta->creatorSummary)) {
    if ($lang == 'de') {
      $meta->creatorSummary = preg_replace(['/and/'],['und'],$meta->creatorSummary);
    }
  }
  /**
   * Some items do not even have a creatorSummary field.
   */
  else {
    $meta->creatorSummary = $data->creators[0]->name ?? $data->creators[0]->lastName;
  }

  $content['title'] = $data->shortTitle !== '' ? $data->shortTitle : $data->title;
  $content['sortkey'] = Str::slug($meta->creatorSummary . '-' . ($meta->parsedDate ?? 'oJ'));
  $content['version'] = $data->version;
  $content['tags'] = implode(',',array_map(function($element) { return $element->tag; },$data->tags));
  $content['collections'] = implode(',',$data->collections);
  $content['creators'] = json_encode($data->creators,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  unset($data->creators,$data->tags,$data->collections);
  $content['meta'] = json_encode(get_object_vars($meta),JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  $content['data'] = json_encode(get_object_vars($data),JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  $content['bib'] = $item->getBib();
  $content['citation'] = $item->getCitation();

  $pageProperties = [
    'slug' => $slug,
    'template' => 'zoteroitem',
    'content'  => $content,
    'title' => $data->title,
    'parent' => $page,
  ];

  $subPage = $page->createChild($pageProperties);
  $subPage->publish();
  $debug && formatPrintLn(['green'],", done and published.");
}

function formatPrint(array $format=[],string $text = '') {
  $codes=[
    'bold'=>1,
    'italic'=>3, 'underline'=>4, 'strikethrough'=>9,
    'black'=>30, 'red'=>31, 'green'=>32, 'yellow'=>33,'blue'=>34, 'magenta'=>35, 'cyan'=>36, 'white'=>37,
    'blackbg'=>40, 'redbg'=>41, 'greenbg'=>42, 'yellowbg'=>44,'bluebg'=>44, 'magentabg'=>45, 'cyanbg'=>46, 'lightgreybg'=>47
  ];
  $formatMap = array_map(function ($v) use ($codes) { return $codes[$v]; }, $format);
  echo "\e[".implode(';',$formatMap).'m'.$text."\e[0m";
}
function formatPrintLn(array $format=[], string $text='') {
  formatPrint($format, $text); echo "\r\n";
}
