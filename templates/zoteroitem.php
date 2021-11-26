<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 * @var string $type
 * @var Kirby\Cms\Pages
 */

use Kirby\Toolkit\Str;

$Meta = $page->meta()->toData('json');
$Data = $page->data()->toData('json');
$authorsOrEditors = $page->creators()->authorsOrEditors();
$notes = $page->files()->filterBy('filename','*','/^note-.*\.json$/');
$cover = $page->image('cover.jpg');
$docs = $page->files()->filterBy('type', 'document');

  if ($kirby->language()->code() !== 'de') {
    $creatorSummary = $Meta['creatorSummary'];
  }
  else {
    $creatorSummary = preg_replace(['/and/'],['und'],$Meta['creatorSummary']);
  }

?>
<?php snippet('header') ?>
<main class="main" id="main">
  <div class="container-lg">
    <?php # snippet('nav-breadcrumb'); ?>
    <div class="grid-flex grid-flex--gutter-large">
      <div class="cell-12 cell-md-8">
        <header>
        <?= $creatorSummary ?>
          <h1><?= $page->title()->toHtml() ?></h1>
        </header>
        <div class="bib-table">
          <div class="bib-table-body">
            <div class="bib-table-row">
              <div class="bib-table-cell bib-table-cell-left"><?= t('zotero.'.key($authorsOrEditors)) ?></div><div class="bib-table-cell bib-table-cell-right"><?= implode("<br />",$authorsOrEditors[key($authorsOrEditors)]) ?></div>
            </div>
          <?php foreach (['title','edition','place','publisher','date','series','seriesNumber','volume','numberOfVolumes','numPages','ISBN','extra'] as $key): ?>
            <?php if ($Data[$key] !== ''): ?>
            <div class="bib-table-row">
              <div class="bib-table-cell bib-table-cell-left"><?= t('zotero.'.$key) ?></div><div class="bib-table-cell bib-table-cell-right"><?= $Data[$key] ?></div>
            </div>
            <?php endif ?>
          <?php endforeach ?>
          </div>
        </div>
        <?php if (count($notes)): ?>
        <div class="bib-notes">
          <h2><?= t('zotero.notes') ?></h2>
          <?php foreach ($notes as $note) { echo json_decode($note->read()); } ?>
        </div>
        <?php endif ?>
      </div>
      <div class="cell-12 cell-md-4">
      <?php if ($cover) : ?>
        <img src="<?php echo $cover->url() ?>" alt="Cover" text="Cover">
      <?php endif; ?>
      <?php if (count($docs)): ?>
        <div class="bib-attachments">
          <h3><?= t('zotero.attachments') ?></h3>
          <ul>
          <?php foreach ($docs as $file): ?>
            <li><i class="far fa-fw <?= $file->faClass() ?>"></i><a href="<?= $file->url() ?>"><?= Str::ucwords(Str::split($file->filename(),'.')[0]) ?></a> (<?= $file->humanSize() ?>)</li>
          <?php endforeach ?>
          </ul>
        </div>
        <?php endif ?>
      </div>
    </div>
  </div>
</main>
<?php snippet('footer') ?>