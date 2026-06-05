<section class="detail-header">
    <div class="detail-school">
        <img src="<?= e(asset($order['school_logo'])) ?>" alt="Logo <?= e($order['school_name']) ?>">
        <div>
            <p class="eyebrow mb-1"><?= e($order['order_no']) ?></p>
            <h1><?= e($order['school_name']) ?></h1>
            <p><?= nl2br(e($order['school_address'])) ?></p>
        </div>
    </div>
    <div class="detail-count">
        <span>Jumlah Foto</span>
        <strong><?= e((string) $order['photo_count']) ?></strong>
    </div>
</section>

<?php if ($students === []): ?>
    <section class="section-panel text-center py-5">
        <p class="mb-0 text-secondary">Belum ada foto siswa pada pesanan ini.</p>
    </section>
<?php else: ?>
    <section class="photo-grid" aria-label="Daftar foto siswa">
        <?php foreach ($students as $student): ?>
            <?php $photoUrl = asset($student['photo_path']); ?>
            <article class="photo-tile">
                <button
                    type="button"
                    class="photo-thumb"
                    data-preview-photo
                    data-photo-src="<?= e($photoUrl) ?>"
                    data-student-name="<?= e($student['student_name']) ?>"
                    title="Preview foto"
                >
                    <img
                        src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=="
                        data-src="<?= e($photoUrl) ?>"
                        alt="Foto <?= e($student['student_name']) ?>"
                        loading="lazy"
                    >
                </button>
                <button
                    type="button"
                    class="photo-copy"
                    data-copy-photo
                    data-photo-src="<?= e($photoUrl) ?>"
                    title="Copy image"
                >
                    Copy
                </button>
                <div class="photo-caption" title="<?= e($student['student_name']) ?>">
                    <?= e($student['student_name']) ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<div class="modal fade" id="photoPreviewModal" tabindex="-1" aria-labelledby="photoPreviewTitle" aria-hidden="true" hidden>
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content preview-modal">
            <div class="modal-header">
                <h2 class="modal-title h5" id="photoPreviewTitle">Preview Foto</h2>
                <button type="button" class="btn-close" data-modal-close aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img id="previewImage" src="" alt="" class="preview-image">
            </div>
            <div class="modal-footer justify-content-between">
                <span id="previewStudentName" class="preview-name"></span>
                <button type="button" class="btn btn-primary" data-modal-copy>
                    Copy Image
                </button>
            </div>
        </div>
    </div>
</div>
