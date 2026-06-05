<section class="section-panel text-center py-5">
    <h1 class="h3">404</h1>
    <p class="text-secondary mb-4"><?= e($message ?? 'Halaman tidak ditemukan.') ?></p>
    <a class="btn btn-primary" href="<?= e(url('/')) ?>">
        Kembali
    </a>
</section>
