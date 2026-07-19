import './bootstrap';

const themeStorageKey = 'theme';
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

function applyTheme(theme, save = false) {
    const dark = theme === 'dark';
    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', String(dark));
        button.setAttribute('aria-label', dark ? 'فعال کردن حالت روشن' : 'فعال کردن حالت تیره');
    });
    if (save) {
        try {
            localStorage.setItem(themeStorageKey, theme);
        } catch (error) {
            // Storage can be unavailable in privacy-restricted contexts.
        }
    }
}

function toggleTheme(button) {
    const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';

    if (! document.startViewTransition || reducedMotion.matches) {
        applyTheme(nextTheme, true);

        return;
    }

    const rect = button.getBoundingClientRect();
    const originX = rect.left + rect.width / 2;
    const originY = rect.top + rect.height / 2;
    const radius = Math.hypot(
        Math.max(originX, window.innerWidth - originX),
        Math.max(originY, window.innerHeight - originY),
    );
    document.documentElement.classList.add('theme-view-transition');

    let transition;

    try {
        transition = document.startViewTransition(() => applyTheme(nextTheme, true));
    } catch (error) {
        document.documentElement.classList.remove('theme-view-transition');
        applyTheme(nextTheme, true);

        return;
    }

    transition.ready
        .then(() => document.documentElement.animate(
            {
                clipPath: [
                    `circle(0px at ${originX}px ${originY}px)`,
                    `circle(${radius}px at ${originX}px ${originY}px)`,
                ],
            },
            {
                duration: 780,
                easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                pseudoElement: '::view-transition-new(root)',
            },
        ))
        .catch(() => {
            // The theme has already switched; animation support is optional.
        });

    transition.finished
        .catch(() => {
            // A canceled transition must still restore normal CSS transitions.
        })
        .finally(() => document.documentElement.classList.remove('theme-view-transition'));
}

document.addEventListener('click', (event) => {
    const button = event.target instanceof Element
        ? event.target.closest('[data-theme-toggle]')
        : null;

    if (button) {
        toggleTheme(button);
    }
});

applyTheme(document.documentElement.classList.contains('dark') ? 'dark' : 'light');

document.querySelectorAll('[data-code-inputs]').forEach((group) => {
    const inputs = [...group.querySelectorAll('input')];
    const codeValue = group.closest('form').querySelector('[data-code-value]');

    const synchronizeCode = () => {
        codeValue.value = inputs.map((input) => input.value).join('');
    };

    inputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '').slice(-1);
            synchronizeCode();
            if (input.value && inputs[index + 1]) inputs[index + 1].focus();
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && !input.value && inputs[index - 1]) inputs[index - 1].focus();
        });

        input.addEventListener('paste', (event) => {
            const pastedCode = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, inputs.length);

            if (! pastedCode) return;

            event.preventDefault();
            inputs.forEach((codeInput, codeIndex) => {
                codeInput.value = pastedCode[codeIndex] ?? '';
            });
            synchronizeCode();
            inputs[Math.min(pastedCode.length, inputs.length) - 1].focus();
        });
    });

    group.closest('form').addEventListener('submit', synchronizeCode);
    synchronizeCode();
});

const initializedResendTimers = new WeakSet();

function readExpiration(storageKey) {
    try {
        const value = Number(localStorage.getItem(storageKey));

        return Number.isFinite(value) ? value : 0;
    } catch (error) {
        return 0;
    }
}

function writeExpiration(storageKey, expiration) {
    try {
        localStorage.setItem(storageKey, String(expiration));
    } catch (error) {
        // The server-side throttle remains authoritative without storage.
    }
}

function initializeResendTimer(timer) {
    if (initializedResendTimers.has(timer)) return;

    const button = timer.querySelector('[data-resend-button]');
    const output = timer.querySelector('[data-countdown-output]');

    if (! button || ! output) return;

    initializedResendTimers.add(timer);

    const duration = Math.max(0, Number(timer.dataset.duration) || 90);
    const storageKey = timer.dataset.storageKey || 'verification_resend_available_at';
    const serverExpiration = Number(timer.dataset.availableAt) || 0;
    const storedExpiration = readExpiration(storageKey);
    const now = Date.now();
    let expiration = Math.max(serverExpiration, storedExpiration);
    let intervalId = null;

    if (expiration <= now && serverExpiration > 0) {
        expiration = serverExpiration;
    }

    if (expiration > now) {
        writeExpiration(storageKey, expiration);
    }

    const render = () => {
        const remaining = Math.max(0, Math.ceil((expiration - Date.now()) / 1000));
        const minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
        const seconds = String(remaining % 60).padStart(2, '0');
        const active = remaining > 0;

        button.disabled = active;
        button.setAttribute('aria-disabled', String(active));
        output.textContent = active
            ? `ارسال مجدد کد تا ${minutes}:${seconds}`
            : 'ارسال مجدد کد';

        if (! active && intervalId !== null) {
            window.clearInterval(intervalId);
            intervalId = null;
        }
    };

    timer.addEventListener('submit', (event) => {
        if (button.disabled) {
            event.preventDefault();

            return;
        }

        expiration = Date.now() + duration * 1000;
        writeExpiration(storageKey, expiration);
        button.disabled = true;
        button.setAttribute('aria-disabled', 'true');
        render();
    });

    render();

    if (expiration > now) {
        intervalId = window.setInterval(render, 1000);
    }

    window.addEventListener('pagehide', () => {
        if (intervalId !== null) {
            window.clearInterval(intervalId);
            intervalId = null;
        }
    }, { once: true });
}

document.querySelectorAll('[data-resend-timer]').forEach(initializeResendTimer);
