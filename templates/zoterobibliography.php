<?php
/**
 * 
 */

if ($page->usepagination()->toBool() === true) {
  $bibitems = $page->children()->sort('sortkey')->paginate($page->chunksize()->toInt());
}
else {
  $bibitems = $page->children()->sort('sortkey');
}
?>
<?php snippet('header') ?>
<main class="main" id="main">
  <div class="container-lg">
    <?php snippet('nav-breadcrumb'); ?>
    <div class="grid-flex grid-flex--gutter-large">
      <div class="cell-12 cell-md-8">
        <header>
          <h1><?php echo $page->title()->html() ?> <?php echo $page->subtitle()->html() ?></h1>
        </header>
        <div class="zotero-wrapper d-hyphen">
          <?php foreach ($bibitems as $bibitem): ?>
            <a style="text-decoration: none;" title="<?= t('zotero.click4details') ?>" href="<?= $bibitem->url() ?>"><?= $bibitem->bib() ?></a>
          <?php endforeach ?>
        </div>
        <?php if ($page->usepagination()->toBool() === true): ?>
          <?php $pagination = $bibitems->pagination() ?>
          <?php if ($pagination->hasPages()): ?>
            <div class="bib-pagination">
              <?php if ($pagination->isFirstPage()): ?>
                <i class="fas fa-angle-double-left" style="color: var(--color-primary-light);"></i>
              <?php else: ?>
                <a class="first" href="<?= $pagination->firstPageUrl() ?>"><i class="fas fa-angle-double-left" style="color: var(--color-primary);"></i></a>
              <?php endif ?>

              <?php if ($pagination->hasPrevPage()): ?>
                <a class="prev" href="<?= $pagination->prevPageURL() ?>"><i class="fas fa-angle-left" style="color: var(--color-primary);"></i></a>
              <?php else: ?>
                <i class="fas fa-angle-left" style="color: var(--color-primary-light);"></i>
              <?php endif ?>

              <?php foreach ($pagination->range(10) as $r): ?>
                <?php if ($pagination->page() === $r): ?>
                  <?= $r ?>
                <?php else: ?>
                  <a aria-current="page" href="<?= $pagination->pageURL($r) ?>"><b><?= $r ?></b></a>
                <?php endif ?>
              <?php endforeach ?>

              <?php if ($pagination->hasNextPage()): ?>
                <a class="next" href="<?= $pagination->nextPageURL() ?>"><b><i class="fas fa-angle-right" style="color: var(--color-primary);"></i></b></a>
              <?php else: ?>
                <i class="fas fa-angle-right" style="color: var(--color-primary-light);"></i>
              <?php endif ?>

              <?php if ($pagination->isLastPage()): ?>
                <i class="fas fa-angle-double-right" style="color: var(--color-primary-light);"></i>
              <?php else: ?>
                <a class="first" href="<?= $pagination->lastPageUrl() ?>"><i class="fas fa-angle-double-right" style="color: var(--color-primary);"></i></a>
              <?php endif ?>
            </div>
          <?php endif ?>
        <?php endif ?>
      </div>
    </div>
  </div>
</main>
<?php snippet('footer') ?>