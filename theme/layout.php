<!doctype html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="theme/css/bootstrap.css">
    <link rel="stylesheet" href="theme/css/style.css">

    <title>Graz.News</title>
  </head>
  <body>
    <header>
      <h1>Graz.News</h1>
    </header>

    <div class="container">

<?php echo $content; ?>

    </div>
    <footer>
      <p>
        Artikel werden automatisch ausgewählt, angeordnet und verlinkt. Die Artikel selbst und ihre Inhalte stehen nicht in Verbindung mit dieser Webseite.
      </p>
      <p>
        <a href="https://github.com/PeterTheOne/graz.news">Webseite</a> © <a href="https://petergrassberger.at">Peter Grassberger</a> (<a href="https://opensource.org/licenses/MIT">MIT Lizenz</a>),
        <a href="https://commons.wikimedia.org/wiki/File:IMG_0515_-_Graz_-_View_from_Schlossberg.JPG">Hintergrundbild</a> © <a href="https://commons.wikimedia.org/wiki/User:Thisisbossi">Andrew Bossi</a> (<a href="https://creativecommons.org/licenses/by-sa/2.5/deed.en">CC-BY-SA-2.5</a>)
      </p>
    </footer>
    <script type="text/javascript">
      var _paq = _paq || [];
      /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
      _paq.push(['trackPageView']);
      _paq.push(['enableLinkTracking']);
      (function() {
        var u="//piwik.graz.news/";
        _paq.push(['setTrackerUrl', u+'piwik.php']);
        _paq.push(['setSiteId', '5']);
        var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
        g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
      })();
    </script>
  </body>
</html>
