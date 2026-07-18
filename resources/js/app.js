import './bootstrap';

function applyTheme(theme, save = false) {
    const dark = theme === 'dark';
    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', String(dark));
        button.setAttribute('aria-label', dark ? 'فعال کردن حالت روشن' : 'فعال کردن حالت تیره');
    });
    if (save) {
        try { localStorage.setItem('theme', theme); } catch (error) { /* Storage is optional. */ }
    }
}

document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => applyTheme(document.documentElement.classList.contains('dark') ? 'light' : 'dark', true));
});
applyTheme(document.documentElement.classList.contains('dark') ? 'dark' : 'light');

document.querySelectorAll('[data-code-inputs]').forEach((group) => {
    const inputs = [...group.querySelectorAll('input')];
    inputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '').slice(-1);
            if (input.value && inputs[index + 1]) inputs[index + 1].focus();
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && !input.value && inputs[index - 1]) inputs[index - 1].focus();
        });
    });
});
