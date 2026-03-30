<?php if (($totalPages ?? 1) > 1): ?>
  <nav aria-label="Paginação" class="mt-3">
    <ul class="pagination justify-content-end mb-0">
      <li class="page-item <?= ($currentPage ?? 1) <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= ($currentPage ?? 1) <= 1 ? '#' : paginationUrl(($currentPage ?? 1) - 1, $queryParams ?? []) ?>">Anterior</a>
      </li>
      <?php for ($p = 1; $p <= ($totalPages ?? 1); $p++): ?>
        <li class="page-item <?= $p === ($currentPage ?? 1) ? 'active' : '' ?>">
          <a class="page-link" href="<?= paginationUrl($p, $queryParams ?? []) ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?= ($currentPage ?? 1) >= ($totalPages ?? 1) ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= ($currentPage ?? 1) >= ($totalPages ?? 1) ? '#' : paginationUrl(($currentPage ?? 1) + 1, $queryParams ?? []) ?>">Próxima</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>
