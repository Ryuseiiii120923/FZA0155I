import "flowbite";

import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
window.flatpickr = flatpickr;

document.addEventListener('livewire:init', () => {
    let handling419 = false;

    Livewire.hook('request', ({ uri, fail }) => {
        fail(({ status, preventDefault }) => {
            if (status !== 419) return;
            preventDefault();
            console.warn('Livewire 419', uri, new Date().toISOString());

            if (handling419) return;
            handling419 = true;

            const loginUrl = document.querySelector('meta[name="login-url"]')?.content ?? '/';
            const last = Number(sessionStorage.getItem('lw419') || 0);

            // loop guard: kung nag-419 ulit sa loob ng 10s pagkatapos ng reload, saka lang sa login
            if (Date.now() - last < 10000) {
                window.location.href = loginUrl;
                return;
            }

            sessionStorage.setItem('lw419', String(Date.now()));
            window.location.reload();
        });
    });
});