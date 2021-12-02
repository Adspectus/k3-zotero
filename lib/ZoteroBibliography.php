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
use Kirby\Toolkit\Dir;
use Kirby\Cms\File;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Str;


function createBibliography($page) {

  $zotero = new ZoteroAPI();
  $zotero->setOptions(kirby()->option('adspectus.zotero'));
  $zotero->setLocale(str_replace('_','-',explode('.',kirby()->language()->locale(LC_ALL))[0]));
  $zotero->setItemType('-attachment || note');
  $zotero->setCollectionKey($page->collectionkey()->toString())->setPath($page->bibtype()->toString());
  $zotero->request()->decodeContent();

  if ($page->deleteitems()->toBool() === true) {
    foreach ($page->children() as $child) {
      $child->delete();
    }
  }

  foreach ($zotero->getItems() as $key => $item) {
    $slug = Str::slug($key);

    $childPage = $page->find($slug);

    if (is_null($childPage)) {
      createAndPublishChild($page,$item);
    }
    else {
      $thisVersion = $childPage->version()->toInt();
      if ($item->getVersion() != $thisVersion) {
        $childPage->delete();
        createAndPublishChild($page,$item);
      }
    }
  }

  $zotero = new ZoteroAPI();
  $zotero->setOptions(kirby()->option('adspectus.zotero'));
  $zotero->setLocale(str_replace('_','-',explode('.',kirby()->language()->locale(LC_ALL))[0]));
  $zotero->setItemType('attachment || note');
  $zotero->setCollectionKey($page->collectionkey()->toString())->setPath($page->bibtype()->toString());
  $zotero->request()->decodeContent();

  foreach ($zotero->getItems() as $item) {
    $data = $item->getData();
    $parentSlug = Str::slug($data->parentItem);
    $parentPage = $page->find($parentSlug);

    if ($data->itemType === 'attachment') {
      $fileName = F::safeName($data->filename);
      if (F::exists($page->root() . '/' . $parentSlug . '/' . $fileName)) {
        $thisFile = $parentPage->file($fileName);
        if ($data->version == $thisFile->version()->toString()) {
          continue;
        }
      }

      $content['caption'] = $data->title;
      $content['version'] = $data->version;

      $zoteroAttachment = new ZoteroAPI();
      $zoteroAttachment->setOptions(kirby()->option('adspectus.zotero'));
      $zoteroAttachment->setFormat('')->setInclude('')->setStyle('')->setSort('');
      $zoteroAttachment->setRawPath('/items/' . $data->key . '/file/view');
      $zoteroAttachment->request();
      $fileContent = $zoteroAttachment->getContent();

      if (isset($fileContent)) {
        if (F::write($page->root() . '/' . $fileName,$fileContent)) {
          try {
            File::create([
              'content' => $content,
              'filename' => $fileName,
              'source' => $page->root() . '/' . $fileName,
              'parent' => $parentPage,
            ]);
          }
          catch (Exception $e) {

          }
          F::remove($page->root() . '/' . $fileName);
        }
      }
    }
    if ($data->itemType === 'note') {
      $note = ['version' => $data->version, 'note' => $data->note];
      $noteFile = $page->root() . '/' . $parentSlug . '/note-' . strtotime($data->dateAdded) . '.json';
      if (F::exists($noteFile)) {
        $thisNote = json_decode(F::read($noteFile));
        if ($data->version == $thisNote->version) {
          continue;
        }
      }
      F::write($noteFile,json_encode($note,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
  }

  
  $page->update(['deleteitems' => false,'refreshitems' => false]);
}

function createAndPublishChild (object $page,object $item) {
  $meta = $item->getMeta();
  $data = $item->getData();
  $slug = Str::slug($data->key);
  $lang = $page->translation()->code();

  if (isset($meta->creatorSummary)) {
    if ($lang == 'de') {
      $meta->creatorSummary = preg_replace(['/and/'],['und'],$meta->creatorSummary);
    }
  }
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
}
