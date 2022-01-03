<?php
/*
  The zoteroitem snippet is used in the zotero plugin (`/site/plugins/k3-zotero`).

*/

use Adspectus\Zotero\ZoteroAPI;
use Kirby\Toolkit\Str;

$useBib = $block->usebib()->toBool();
$slug = Str::slug($block->itemkey()->toString());

if ($useBib) {
  $page = $kirby->page($block->bibpage()->toString())->children()->find($slug);
  $zoteroItems[$page->data()->toData('json')['key']] = ['bib' => $page->bib()->toString() ];
}

if (!isset($zoteroItems)) {
  $useBib = false;
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
    'style'         => $block->citationstyle()->toString(),
    'itemKey'       => $block->itemkey()->toString(),
  ];

  $apiOptions = array_merge(kirby()->option('adspectus.zotero'),$apiOptions);

  $zotero = new ZoteroAPI();
  $zotero->setOptions($apiOptions);
  $zotero->setLocale(str_replace('_','-',explode('.',kirby()->language()->locale(LC_ALL))[0]));
  $zotero->setItemKey($apiOptions['itemKey']);
  $zotero->setPath('item');

  $zotero->request()->decodeContent();
  foreach ($zotero->getItems() as $key => $value) {
    $zoteroItems[$key] = ['bib' => $value->getBib() ];
  }
}
?>

<div class="zotero-wrapper d-hyphen">
  <?php foreach ($zoteroItems as $key => $item): ?>
    <?php if ($useBib): ?>
      <a style="text-decoration: none;" title="<?= t('zotero.click4details') ?>" href="/<?= $block->bibpage()->toString() ?>/<?= Str::slug($key) ?>"><?= $item['bib'] ?></a>
    <?php else: ?>
      <?= $item['bib'] ?>
    <?php endif ?>
  <?php endforeach ?>
</div>