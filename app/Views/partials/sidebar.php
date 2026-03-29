<nav class="col-md-2 d-md-block sidebar py-4">
    <div class="d-flex flex-column h-100 px-2">
        <h5 class="text-white text-center mb-4">Locadora</h5>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="<?= url('/') ?>">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('/vehicles') ?>">Veículos</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('/vehicles/mileage-history') ?>">Histórico KM</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('/clients') ?>">Clientes</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('/rentals') ?>">Locações</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('/maintenances') ?>">Manutenções</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('/financial') ?>">Finanças</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('/reports') ?>">Relatórios</a></li>
        </ul>

        <div class="mt-auto pt-3">
            <form method="POST" action="<?= url('/logout') ?>" class="px-1">
                <input type="hidden" name="_token" value="<?= csrfToken() ?>">
                <button class="btn btn-outline-light btn-sm w-100 text-start">Sair (<?= esc(authUser()['nome'] ?? '') ?>)</button>
            </form>
        </div>
    </div>
</nav>
