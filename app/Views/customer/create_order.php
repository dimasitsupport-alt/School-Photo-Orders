<div class="row justify-content-center">
    <div class="col-xl-8 col-lg-10">
        <section class="section-panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Customer</p>
                    <h1>Buat Pesanan Sekolah</h1>
                </div>
            </div>

            <form method="post" action="<?= e(url('/orders')) ?>" enctype="multipart/form-data" class="row g-4">
                <?= csrf_field() ?>

                <div class="col-md-6">
                    <label for="order_no" class="form-label">Nomor Pesanan</label>
                    <input
                        type="text"
                        id="order_no"
                        name="order_no"
                        class="form-control form-control-lg"
                        value="<?= e(old('order_no')) ?>"
                        placeholder="ORD001"
                        maxlength="60"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label for="school_name" class="form-label">Nama Sekolah</label>
                    <input
                        type="text"
                        id="school_name"
                        name="school_name"
                        class="form-control form-control-lg"
                        value="<?= e(old('school_name')) ?>"
                        maxlength="160"
                        required
                    >
                </div>

                <div class="col-12">
                    <label for="school_logo" class="form-label">Logo Sekolah</label>
                    <input
                        type="file"
                        id="school_logo"
                        name="school_logo"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        required
                    >
                </div>

                <div class="col-12">
                    <label for="school_address" class="form-label">Alamat Sekolah</label>
                    <textarea
                        id="school_address"
                        name="school_address"
                        class="form-control"
                        rows="4"
                        required
                    ><?= e(old('school_address')) ?></textarea>
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary btn-lg" type="submit">
                        Buat Pesanan
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
