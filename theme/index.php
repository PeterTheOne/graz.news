<div class="row">
  <?php
  foreach ($articles as $article) {
  ?>
    <div class="col-lg-4 col-sm-6">
      <article class="article">
        <!-- todo: check security -->
        <header>
          <span><?php echo $article->site; ?></span> -
          <span><?php echo date_format((new DateTime())->setTimestamp($article->created), 'd.m.Y H:i:s'); ?></span>
        </header>
        <h2><a href="<?php echo $article->link; ?>" target="_blank"><?php echo $article->title; ?></a></h2>
      </article>
    </div>
  <?php
  }
  ?>
</div>
