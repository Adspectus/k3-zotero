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

if (kirby()->option('debug')) {
  ini_set('xdebug.var_display_max_children', -1);
  ini_set('xdebug.var_display_max_data', -1);
  ini_set('xdebug.var_display_max_depth', 10);
}

Kirby::plugin('adspectus/zotero', [
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
  'hooks' => [
    'page.update:after' => function($newPage) {
      if ($newPage->template() == 'zoterobibliography') {
        createBibliography($newPage);
      }
    }
  ],
  'blueprints' => [
    'pages/zoterobibliography' => __DIR__ . '/blueprints/zoterobibliography.yml',
    'blocks/zoterolist' => __DIR__ . '/blueprints/blocks/zoterolist.yml',
    'blocks/zoteroitem' => __DIR__ . '/blueprints/blocks/zoteroitem.yml',
  ],
  'snippets' => [
    'blocks/zoterolist' => __DIR__ . '/snippets/blocks/zoterolist.php',
    'blocks/zoteroitem' => __DIR__ . '/snippets/blocks/zoteroitem.php',
  ],
  'templates' => [
    'zoterobibliography' => __DIR__ . '/templates/zoterobibliography.php',
    'zoteroitem' => __DIR__ . '/templates/zoteroitem.php',
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
  'fileMethods' => [
    'faClass' => function() {
      switch ($this->extension()) {
        case 'pdf': return 'fa-file-pdf'; break;
        default: return 'fa-file';
      }
    },
    'humanSize' => function($decimals = 2) {
      $sz = array('B','KB','MB','GB');
      $factor = floor((strlen($this->size()) - 1) / 3);
      return sprintf("%.{$decimals}f", $this->size() / pow(1024, $factor)) . $sz[$factor];
    }
  ],
  'translations' => [
    'en' => Yaml::decode(F::read(__DIR__ . '/translations/en.yml')),
    'de' => Yaml::decode(F::read(__DIR__ . '/translations/de.yml'))
  ]
]);

