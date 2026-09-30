</main>
<footer class="footer">
  <div class="container footer-inner">
    <span>✈ TripMate Plan · Share · Split</span>
  </div>
</footer>
<script>
  window.TRIPMATE = <?= json_encode(
      ['baseUrl' => BASE_URL, 'csrf' => csrf_token(), 'data' => $jsData ?? new stdClass()],
      JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
  ) ?>;
</script>
<?php foreach (array_merge(['main.js'], $pageScripts ?? []) as $js): ?>
  <?php if (file_exists(__DIR__ . '/../assets/js/' . $js)): ?>
    <script src="<?= BASE_URL ?>/assets/js/<?= e($js) ?>" defer></script>
  <?php endif; ?>
<?php endforeach; ?>
</body>
</html>
