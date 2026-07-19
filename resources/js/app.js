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
