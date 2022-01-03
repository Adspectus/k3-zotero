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


use Kirby\Cms\App as Kirby;
use Kirby\Data\Yaml;
use Kirby\Toolkit\F;

load([
  'Adspectus\\Zotero\\ZoteroAPI' => 'lib/ZoteroAPI.php',
], __DIR__);

require __DIR__ . '/lib/ZoteroBibliography.php';

if (kirby()->option('debug')) { # The following settings might cause memeory issues (503 error in browser)
#  ini_set('xdebug.var_display_max_children', -1); # Default: 128
  ini_set('xdebug.var_display_max_data', -1); # Default: 512
#  ini_set('xdebug.var_display_max_depth', 10); # Default: 3
}

Kirby::plugin('adspectus/zotero', [
  'blueprints' => [
    'pages/zoterobibliography' => __DIR__ . '/blueprints/zoterobibliography.yml',
    'blocks/zoterolist' => __DIR__ . '/blueprints/blocks/zoterolist.yml',
    'blocks/zoteroitem' => __DIR__ . '/blueprints/blocks/zoteroitem.yml',
  ],
  'fieldMethods' => [
    'mergeCreators' => function($field) {
      $creators = $field->toData('json');
      foreach ($creators as $creator) {
        if (isset($creator['name'])) {
          $name = $creator['name'];
        }
        if (isset($creator['firstName'])) {
          $name = $creator['firstName'];
        }
        if (isset($creator['lastName'])) {
          $name .= (isset($creator['firstName']) ? ' ' : '');
          $name .= $creator['lastName'];
        }
        $newCreators[] = [ $creator['creatorType'] => $name ];
      }
      return $newCreators;
    }
  ],
  'hooks' => [
    'page.update:after' => function($newPage,$oldPage) {
      if ($newPage->template() == 'zoterobibliography') {
        if ($newPage->isPublished()) {
          if ($newPage->deleteitems()->toBool() === true) {
            deleteBibliography($newPage);
          }
          createBibliography($newPage);
        }
      }
    },
    'page.changeStatus:before' => function($page,$status,$position) {
      if ($page->template() == 'zoterobibliography') {
        if ($status == 'draft') {
          deleteBibliography($page);
        }
      }
    },
    'page.changeStatus:after' => function($newPage,$oldPage) {
      if ($newPage->template() == 'zoterobibliography') {
        if ($newPage->isPublished()) {
          createBibliography($newPage);
        }
      }
    }
  ],
  'options' => [
    'cache'        => true,
    'apiKey'       => '',
    'userID'       => '',
    'groupID'      => '',
    'locale'       => 'en_US',
    'format'       => 'json',
    'include'      => 'data',
    'exportFormat' => '',
    'style'        => 'chicago-note-bibliography',
    'sort'         => 'dateModified',
    'itemType'     => '',
    'limit'        => 25,
    'start'        => 0,
  ],
  'snippets' => [
    'blocks/zoterolist' => __DIR__ . '/snippets/blocks/zoterolist.php',
    'blocks/zoteroitem' => __DIR__ . '/snippets/blocks/zoteroitem.php',
  ],
  'templates' => [
    'zoterobibliography' => __DIR__ . '/templates/zoterobibliography.php',
    'zoteroitem' => __DIR__ . '/templates/zoteroitem.php',
  ],
  'translations' => [
    'en' => Yaml::decode(F::read(__DIR__ . '/translations/en.yml')),
    'de' => Yaml::decode(F::read(__DIR__ . '/translations/de.yml'))
  ]
]);
