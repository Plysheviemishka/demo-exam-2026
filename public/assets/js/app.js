(function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach((alert) => {
        setTimeout(() => alert.classList.add('alert--hidden'), 4500);
    });
})();
