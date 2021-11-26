<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 * @var string $type
 * @var Kirby\Cms\Pages
 */
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
        <div class="zotero-wrapper">
          <?php foreach ($page->children() as $bibitem): ?>
            <a style="text-decoration: none;" title="<?= t('zotero.click4details') ?>" href="<?= $bibitem->url() ?>"><?= $bibitem->bib() ?></a>
          <?php endforeach ?>
        </div>
      </div>
    </div>
  </div>
</main>
<?php snippet('footer') ?>