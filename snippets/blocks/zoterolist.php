<?php
/*
  The zoterolist snippet is used in the zotero plugin (`/site/plugins/k3-zotero`).

*/

use Adspectus\Zotero\ZoteroAPI;
use Kirby\Toolkit\Str;

$bibType = $block->bibtype()->toString();
$collectionKey = substr($block->collectionkey()->toString(),0,8);
$tags = array_map('trim',explode(',',$block->tags()->toString()));

if ($block->usebib()->toBool()) {
  if ($bibType == 'all' || $bibType == 'mypub') {
    $children = page($block->bibpage()->toString())->children();
  }
  if ($bibType == 'collection') {
    $children = page($block->bibpage()->toString())->children()->filterBy('collections',$collectionKey,',');
  }
  if ($bibType == 'tags') {
    $children = page($block->bibpage()->toString())->children()->filterBy('tags','in',$tags,',');
  }
  foreach ($children->sort('sortkey') as $page) {
    $zoteroItems[$page->data()->toData('json')['key']] = ['bib' => $page->bib()->toString() ];
  }
}

if (!isset($zoteroItems)) {
  if ($block->apikey()->toString() !== '') {
    $apiOptions['apiKey'] = $block->apikey()->toString();
  }
  if ($block->userid()->toString() !== '') {
    $apiOptions['userID'] = $block->userid()->toString();
  }
  if ($block->groupid()->toString() !== '') {
    $apiOptions['groupID'] = $block->groupid()->toString();
  }

  $apiOptions = [
    'format'        => $block->apiformat()->toString(),
    'include'       => str_replace(' ','',$block->include()->toString()),
    'exportFormat'  => str_replace(' ','',$block->exportformat()->toString()),
    'itemType'      => '-attachment || note',
    'style'         => $block->citationstyle()->toString(),
    'sort'          => $block->sortfield()->toString(),
    'collectionKey' => substr($block->collectionkey()->toString(),0,8),
    'tags'          => implode(',',$tags),
  ];

  $apiOptions = array_merge(kirby()->option('adspectus.zotero'),$apiOptions);

  $zotero = new ZoteroAPI();
  $zotero->setOptions($apiOptions);
  $zotero->setLocale(str_replace('_','-',explode('.',kirby()->language()->locale(LC_ALL))[0]));
  $zotero->setItemType($apiOptions['itemType']);
  if ($bibType == 'collection') { $zotero->setCollectionKey($apiOptions['collectionKey']); }
  if ($bibType == 'tags') { $zotero->setTags($apiOptions['tags']); }
  $zotero->setPath($bibType);

  $zotero->request()->decodeContent();
  foreach ($zotero->getItems() as $key => $value) {
    $zoteroItems[$key] = ['bib' => $value->getBib() ];
  }
}
?>

<div class="zotero-wrapper d-hyphen">
<?php foreach ($zoteroItems as $key => $item): ?>
  <?php if ($block->usebib()->toBool()): ?>
    <a style="text-decoration: none;" title="<?= t('zotero.click4details') ?>" href="/<?= $block->bibpage()->toString() ?>/<?= Str::slug($key) ?>"><?= $item['bib'] ?></a>
  <?php else: ?>
    <?= $item['bib'] ?>
  <?php endif ?>
<?php endforeach ?>
</div>
