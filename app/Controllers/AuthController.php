<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login');
    }

    public function login(): void
    {
        validateCsrf();

        $login = trim($_POST['login'] ?? '');
        $password = $_POST['senha'] ?? '';

        if (!$login || !$password) {
            flash('error', 'Preencha login/e-mail e senha.');
            $this->redirect('/login');
        }

        $userModel = new User();
        $user = $userModel->findByLoginOrEmail($login);

        if (!$user || !password_verify($password, $user['senha'])) {
            flash('error', 'Credenciais inválidas.');
            $this->redirect('/login');
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'login' => $user['login'],
            'perfil' => $user['perfil'],
            'first_login' => (int)($user['primeiro_login'] ?? 0),
        ];

        flash('success', 'Login realizado com sucesso.');
        $this->redirect('/');
    }

    public function logout(): void
    {
        validateCsrf();
        session_unset();
        session_destroy();
        session_start();
        flash('success', 'Sessão encerrada.');
        $this->redirect('/login');
    }
}
