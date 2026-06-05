<div class="row g-4">
    <div class="col-xl-4">
        <section class="section-panel sticky-lg-top school-summary">
            <img src="<?= e(asset($order['school_logo'])) ?>" alt="Logo <?= e($order['school_name']) ?>" class="school-logo">
            <p class="eyebrow mt-3">Nomor Pesanan</p>
            <h1 class="h3 mb-2"><?= e($order['order_no']) ?></h1>
            <h2 class="h5"><?= e($order['school_name']) ?></h2>
            <p class="text-secondary mb-3"><?= nl2br(e($order['school_address'])) ?></p>
            <div class="metric-strip">
                <span>Jumlah Foto</span>
                <strong><?= e((string) $order['photo_count']) ?></strong>
            </div>
        </section>
    </div>

    <div class="col-xl-8">
        <section class="section-panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Customer</p>
                    <h1>Tambah Data Siswa</h1>
                </div>
            </div>

            <form method="post" action="<?= e(url('/orders/' . rawurlencode($order['order_no']) . '/students')) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div data-upload-rows class="upload-rows">
                    <div class="upload-row" data-upload-row>
                        <div>
                            <label class="form-label">Nama Siswa</label>
                            <input type="text" name="student_names[]" class="form-control" maxlength="160">
                        </div>
                        <div>
                            <label class="form-label">Foto</label>
                            <input type="file" name="photos[]" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        </div>
                        <button type="button" class="btn btn-outline-danger icon-btn align-self-end" data-remove-row title="Hapus baris">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="btn btn-outline-primary" data-add-row>
                        Tambah Baris
                    </button>
                    <label class="form-check upload-option">
                        <input class="form-check-input" type="checkbox" name="remove_background" value="1">
                        <span class="form-check-label">Hapus background</span>
                    </label>
                </div>

                <hr class="my-4">

                <div class="row g-3">
                    <div class="col-md-7">
                        <label for="bulk_photos" class="form-label">Upload Massal Foto</label>
                        <input
                            type="file"
                            id="bulk_photos"
                            name="bulk_photos[]"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            multiple
                        >
                    </div>
                    <div class="col-md-5">
                        <label for="bulk_student_names" class="form-label">Nama Siswa Massal</label>
                        <textarea
                            id="bulk_student_names"
                            name="bulk_student_names"
                            class="form-control"
                            rows="5"
                            placeholder="Satu nama per baris"
                        ></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-primary btn-lg" type="submit">
                        Simpan Foto Siswa
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
