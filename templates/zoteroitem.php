<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 * @var string $type
 * @var Kirby\Cms\Pages
 */

use Kirby\Toolkit\Str;

$meta = $page->meta()->toData('json');
$data = $page->data()->toData('json');
$creators = $page->creators()->mergeCreators();
$notes = $page->files()->filterBy('filename','*','/^note-.*\.json$/');
$cover = $page->image('cover.jpg') ?? $page->image('cover.png');
$docs = $page->files()->filterBy('type', 'document');

?>
<?php snippet('header') ?>
<main class="main" id="main">
  <div class="container-lg">
    <?php # snippet('nav-breadcrumb'); ?>
    <div class="grid-flex grid-flex--gutter-large">
      <div class="cell-12 cell-md-8">
        <header>
        <?= $meta['creatorSummary'] ?>
          <h1><?= $page->title()->toHtml() ?></h1>
        </header>
        <div class="bib-table">
          <div class="bib-table-body">
              <?php foreach ($creators as $num => $creator): ?>
                <div class="bib-table-row">
                  <?php if (count($creators) == 1): ?>
                    <div class="bib-table-cell bib-table-cell-left"><?= t('zotero.'.key($creator)) ?></div>
                    <div class="bib-table-cell bib-table-cell-right d-hyphen"><?= $creator[key($creator)] ?></div>
                  <?php else: ?>
                    <?php if ($num == 0): ?>
                      <div class="bib-table-cell bib-table-cell-left bib-table-cell-top"><?= t('zotero.'.key($creator)) ?></div>
                      <div class="bib-table-cell bib-table-cell-right bib-table-cell-top d-hyphen"><?= $creator[key($creator)] ?></div>
                    <?php elseif ($num == count($creators)-1): ?>
                      <?php if (key($creator) == key($creators[$num-1])): ?>
                        <div class="bib-table-cell bib-table-cell-left bib-table-cell-bot"></div>
                      <?php else: ?>
                        <div class="bib-table-cell bib-table-cell-left bib-table-cell-bot"><?= t('zotero.'.key($creator)) ?></div>
                      <?php endif ?>
                      <div class="bib-table-cell bib-table-cell-right bib-table-cell-bot d-hyphen"><?= $creator[key($creator)] ?></div>
                    <?php else: ?>
                      <?php if (key($creator) == key($creators[$num-1])): ?>
                        <div class="bib-table-cell bib-table-cell-left bib-table-cell-bot"></div>
                      <?php else: ?>
                        <div class="bib-table-cell bib-table-cell-left bib-table-cell-bot"><?= t('zotero.'.key($creator)) ?></div>
                      <?php endif ?>
                      <div class="bib-table-cell bib-table-cell-right bib-table-cell-mid d-hyphen"><?= $creator[key($creator)] ?></div>
                    <?php endif ?>
                  <?php endif ?>
                </div>
              <?php endforeach ?>
              <?php foreach (['title','edition','place','publisher','date','series','seriesNumber','volume','numberOfVolumes','numPages','ISBN'] as $key): ?>
              <?php if (isset($data[$key]) && $data[$key] !== ''): ?>
                <div class="bib-table-row">
                  <div class="bib-table-cell bib-table-cell-left"><?= t('zotero.'.$key) ?></div>
                  <div class="bib-table-cell bib-table-cell-right d-hyphen"><?= $data[$key] ?></div>
                </div>
              <?php endif ?>
            <?php endforeach ?>

            <?php if (isset($data['extra']) && $data['extra'] !== ''): ?>
              <div class="bib-table-row">
                <div class="bib-table-cell bib-table-cell-left"><?= t('zotero.extra') ?></div>
                <div class="bib-table-cell bib-table-cell-right d-hyphen">
                <?php foreach (preg_split('/\n+/',$data['extra']) as $index => $extra) {
                  list($key,$val) = preg_split('/\s*:\s*/',$extra,2);
                  if ($index > 0) { echo '<br />'; }
                  if (isset($key)) {
                    if (str_contains($key,'Link')) {
                      echo '<a href="'.$val.'" target="_blank">'.$key.'</a>';
                    }
                    else {
                      echo (empty($key) ? '' : $key . ': ') . $val;
                    }
                  }
                  else {
                    echo $val;
                  }
                } ?>
                </div>
              </div>
            <?php endif ?>

          </div>
        </div>
        <?php if (count($docs)): ?>
          <div class="bib-attachments d-hyphen">
            <h2><?= t('zotero.attachments') ?></h2>
            <ul>
            <?php foreach ($docs as $file): ?>
              <li><i class="far fa-fw <?= $file->faClass() ?>"></i><a href="<?= $file->url() ?>"><?= $file->caption() ?></a> (<?= $file->humanSize() ?>)</li>
            <?php endforeach ?>
            </ul>
          </div>
        <?php endif ?>
      </div>
      <div class="cell-12 cell-md-4">
        <?php if ($cover) : ?>
          <img src="<?php echo $cover->url() ?>" alt="Cover" text="Cover">
        <?php endif; ?>
        <?php if (count($notes)): ?>
          <div class="bib-notes d-hyphen">
            <h2><?= t('zotero.notes') ?></h2>
            <?php foreach ($notes as $note) {
              $noteContent = json_decode($note->read());
              echo $noteContent->note;
            } ?>
          </div>
        <?php endif ?>
      </div>
    </div>
  </div>
</main>
<?php snippet('footer') ?>