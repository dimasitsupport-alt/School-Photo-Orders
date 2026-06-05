<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <section class="section-panel auth-panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Admin</p>
                    <h1>Login Admin</h1>
                </div>
            </div>

            <form method="post" action="<?= e(url('/admin/login')) ?>" class="row g-3">
                <?= csrf_field() ?>

                <div class="col-12">
                    <label for="username" class="form-label">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control form-control-lg"
                        value="<?= e(old('username')) ?>"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="col-12">
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control form-control-lg"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="col-12">
                    <button class="btn btn-primary btn-lg w-100" type="submit">
                        Login
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
