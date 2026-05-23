<div class="row">
  <?php
  foreach ($clusters as $cluster) {
      $primary = $cluster[0];
      $others = array_slice($cluster, 1);
  ?>
    <div class="col-lg-4 col-sm-6">
      <article class="article">
        <header>
          <span><?php echo htmlentities($primary->site, ENT_QUOTES, 'UTF-8'); ?></span> -
          <span title="<?php echo date_format((new DateTime())->setTimestamp((int) $primary->created), 'd.m.Y H:i:s'); ?>"><?php echo timestampToPrettyDate((int) $primary->created); ?></span>
        </header>
        <h2><a href="<?php echo htmlentities($primary->link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank"><?php echo htmlentities($primary->title, ENT_QUOTES, 'UTF-8'); ?></a></h2>
        <?php if ($others): ?>
          <ul class="article-also">
            <?php foreach ($others as $other): ?>
              <li>
                <a href="<?php echo htmlentities($other->link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                  <span class="article-also-site"><?php echo htmlentities($other->site, ENT_QUOTES, 'UTF-8'); ?>:</span>
                  <?php echo htmlentities($other->title, ENT_QUOTES, 'UTF-8'); ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </article>
    </div>
  <?php
  }
  ?>
</div>
