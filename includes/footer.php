<?php if (!empty($page_js)): foreach ((array)$page_js as $js): ?>
  <script src="<?= e(url('assets/js/' . $js)) ?>" defer></script>
<?php endforeach; endif; ?>
</body>
</html>