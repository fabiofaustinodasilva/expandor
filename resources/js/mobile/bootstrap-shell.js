import { MobileAuthService } from './mobile-auth-service.js';

function $(id) {
    return document.getElementById(id);
}

function show(id, on) {
    const el = $(id);
    if (!el) {
        return;
    }

    el.hidden = !on;
}

function setBanner(text, kind = 'error') {
    const el = $('auth-banner');
    if (!el) {
        return;
    }

    if (!text) {
        el.hidden = true;
        el.textContent = '';

        return;
    }

    el.hidden = false;
    el.dataset.kind = kind;
    el.textContent = text;
}

function paintUser(data) {
    const name = data?.user?.name || data?.name || '';
    const email = data?.user?.email || data?.email || '';
    const company = data?.company?.name || data?.user?.company?.name || '';
    const hello = $('signed-hello');
    if (hello) {
        hello.textContent = name ? `Olá, ${name}` : 'Sessão ativa';
    }
    const meta = $('signed-meta');
    if (meta) {
        meta.textContent = [company, email].filter(Boolean).join(' · ');
    }
}

async function enterApp(data) {
    paintUser(data);
    show('screen-login', false);
    show('screen-app', true);
    setBanner('');
}

function showLogin(message) {
    show('screen-app', false);
    show('screen-login', true);
    if (message) {
        setBanner(message, 'status');
    }
}

async function onSubmit(event) {
    event.preventDefault();
    const button = $('login-submit');
    const email = $('login-email')?.value?.trim();
    const password = $('login-password')?.value ?? '';
    if (button) {
        button.disabled = true;
    }
    setBanner('');

    try {
        const data = await MobileAuthService.login(email, password);
        await enterApp(data);
    } catch (error) {
        if (error.code === 'network_offline') {
            setBanner(error.message, 'error');
        } else if (error.status === 500) {
            setBanner('Serviço indisponível. Tente novamente em instantes.', 'error');
        } else {
            setBanner(error.message || 'Não foi possível entrar.', 'error');
        }
    } finally {
        if (button) {
            button.disabled = false;
        }
    }
}

async function onLogout() {
    await MobileAuthService.logout();
    showLogin();
    const password = $('login-password');
    if (password) {
        password.value = '';
    }
}

async function startup() {
    const loginForm = $('expandor-login-form');
    if (!loginForm) {
        return;
    }

    loginForm.addEventListener('submit', onSubmit);
    $('login-forgot')?.addEventListener('click', (event) => {
        event.preventDefault();
        MobileAuthService.openForgotPassword();
    });
    $('logout-button')?.addEventListener('click', onLogout);
    window.addEventListener('expandor:auth-cleared', (event) => {
        showLogin(event.detail?.message || '');
    });

    try {
        const user = await MobileAuthService.getCurrentUser();
        if (user) {
            await enterApp(user);

            return;
        }
    } catch (error) {
        if (error.code === 'network_offline') {
            setBanner('Sem conexão. Conecte-se para entrar. O modo offline ainda não está disponível.', 'error');
        }
    }

    showLogin(MobileAuthService.lastAuthMessage);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startup);
} else {
    startup();
}
