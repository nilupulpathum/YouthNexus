<?php $pageScripts = isset($pageScripts) && is_array($pageScripts) ? $pageScripts : []; ?>
        </div>
      </main>

      <?php require __DIR__ . '/../partials/dashboard-footer.view.php'; ?>
    </div>
  </div>

  <script src="<?= ROOT ?>/assets/js/dashboard.js" defer></script>
  <?php foreach ($pageScripts as $pageScript): ?>
    <script src="<?= htmlspecialchars((string) $pageScript, ENT_QUOTES, 'UTF-8') ?>" defer></script>
  <?php endforeach; ?>
</body>
</html>
