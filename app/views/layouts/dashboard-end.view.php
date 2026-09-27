<?php $pageScripts = isset($pageScripts) && is_array($pageScripts) ? $pageScripts : []; ?>
        </div>
      </main>

      <?php require __DIR__ . '/../partials/dashboard-footer.view.php'; ?>
    </div>
  </div>

  <script src="<?= ROOT ?>/assets/js/dashboard.js?v=<?= rawurlencode(ASSET_VERSION) ?>" defer></script>
  <?php foreach ($pageScripts as $pageScript): ?>
    <?php $scriptUrl = (string) $pageScript; ?>
    <script src="<?= htmlspecialchars($scriptUrl . (str_contains($scriptUrl, '?') ? '&' : '?') . 'v=' . rawurlencode(ASSET_VERSION), ENT_QUOTES, 'UTF-8') ?>" defer></script>
  <?php endforeach; ?>
</body>
</html>
