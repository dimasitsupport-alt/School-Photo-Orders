<section class="section-panel">
    <div class="section-heading align-items-end">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Dashboard Pesanan</h1>
        </div>
    </div>

    <form class="admin-search" data-admin-search data-endpoint="<?= e(url('/admin/search')) ?>">
        <div class="input-group input-group-lg">
            <span class="input-group-text" aria-hidden="true">Cari</span>
            <input
                type="search"
                name="order_no"
                class="form-control"
                placeholder="Cari nomor pesanan, contoh ORD001"
                autocomplete="off"
                autofocus
            >
            <button class="btn btn-primary" type="submit">Cari</button>
        </div>
    </form>
</section>

<div class="search-meta" data-search-status></div>
<div class="row g-3" data-search-results></div>
