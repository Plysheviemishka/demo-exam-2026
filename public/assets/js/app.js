(function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach((alert) => {
        setTimeout(() => alert.classList.add('alert--hidden'), 4500);
    });

    document.querySelectorAll('[data-date-mask]').forEach((input) => {
        input.addEventListener('input', () => {
            const digits = input.value.replace(/\D/g, '').slice(0, 8);
            const parts = [digits.slice(0, 2), digits.slice(2, 4), digits.slice(4, 8)].filter(Boolean);
            input.value = parts.join('.');
        });
    });

    document.querySelectorAll('[data-smart-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            let valid = true;
            form.querySelectorAll('[required]').forEach((field) => {
                const oldError = field.parentElement.querySelector('.client-error');
                if (oldError) oldError.remove();
                field.classList.remove('is-invalid');

                if (!field.value.trim() || !field.checkValidity()) {
                    valid = false;
                    field.classList.add('is-invalid');
                    const error = document.createElement('span');
                    error.className = 'client-error';
                    error.textContent = field.dataset.requiredMessage || 'Заполните поле корректно';
                    field.insertAdjacentElement('afterend', error);
                }
            });

            if (!valid) event.preventDefault();
        });
    });

    const track = document.querySelector('[data-slider-track]');
    if (track) {
        const slides = Array.from(track.children);
        const prev = document.querySelector('[data-slider-prev]');
        const next = document.querySelector('[data-slider-next]');
        const dotsContainer = document.querySelector('[data-slider-dots]');
        let index = 0;
        let timer = null;

        const dots = slides.map((_, dotIndex) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'slider__dot';
            dot.setAttribute('aria-label', `Перейти к слайду ${dotIndex + 1}`);
            dot.addEventListener('click', () => goTo(dotIndex));
            dotsContainer.append(dot);
            return dot;
        });

        function render() {
            track.style.transform = `translateX(-${index * 100}%)`;
            dots.forEach((dot, dotIndex) => dot.classList.toggle('slider__dot--active', dotIndex === index));
        }

        function goTo(nextIndex) {
            index = (nextIndex + slides.length) % slides.length;
            render();
            restart();
        }

        function restart() {
            clearInterval(timer);
            timer = setInterval(() => goTo(index + 1), 3000);
        }

        prev && prev.addEventListener('click', () => goTo(index - 1));
        next && next.addEventListener('click', () => goTo(index + 1));
        render();
        restart();
    }

    const modal = document.querySelector('[data-modal]');
    const modalText = document.querySelector('[data-modal-text]');
    const modalSubmit = document.querySelector('[data-modal-submit]');
    let pendingForm = null;

    document.querySelectorAll('[data-confirm-status]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') return;
            event.preventDefault();
            pendingForm = form;
            const select = form.querySelector('select[name="status"]');
            modalText.textContent = `Сохранить статус «${select.value}» для выбранной заявки?`;
            modal.hidden = false;
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => {
            modal.hidden = true;
            pendingForm = null;
        });
    });

    modalSubmit && modalSubmit.addEventListener('click', () => {
        if (!pendingForm) return;
        pendingForm.dataset.confirmed = 'true';
        pendingForm.submit();
    });
})();
