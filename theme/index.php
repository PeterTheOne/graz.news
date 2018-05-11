<div class="row">
  <?php
  foreach ($articles as $article) {
  ?>
    <div class="col-lg-4 col-sm-6">
      <article class="article">
        <header>
          <span><?php echo htmlentities($article->site, ENT_QUOTES, 'UTF-8'); ?></span> -
          <span><?php echo htmlentities(date_format((new DateTime())->setTimestamp($article->created), 'd.m.Y H:i:s'), ENT_QUOTES, 'UTF-8'); ?></span>
        </header>
        <h2><a href="<?php echo htmlentities($article->link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank"><?php echo htmlentities($article->title, ENT_QUOTES, 'UTF-8'); ?></a></h2>
      </article>
    </div>
  <?php
  }
  ?>
</div>
