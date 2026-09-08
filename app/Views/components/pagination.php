<?php if (($totalPages ?? 1) > 1): ?>
    <nav aria-label="Paginación" class="pagination">
        <?php for ($pageNumber = 1; $pageNumber <= (int) $totalPages; $pageNumber++): ?>
            <a
                href="<?php echo htmlspecialchars((string) ($url ?? '#') . $pageNumber, ENT_QUOTES, 'UTF-8'); ?>"
                class="pagination__link<?php echo $pageNumber === (int) ($currentPage ?? 1) ? ' pagination__link--active' : ''; ?>"
            >
                <?php echo $pageNumber; ?>
            </a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
