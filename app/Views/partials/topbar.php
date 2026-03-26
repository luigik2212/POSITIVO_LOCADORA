<div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
    <h4 class="mb-0">Painel Administrativo</h4>
    <form method="POST" action="/logout">
        <input type="hidden" name="_token" value="<?= csrfToken() ?>">
        <button class="btn btn-outline-danger btn-sm">Sair (<?= esc(authUser()['nome'] ?? '') ?>)</button>
    </form>
</div>
