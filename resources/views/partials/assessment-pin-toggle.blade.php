<script>
document.querySelectorAll('[data-toggle-pin]').forEach(function (button) {
    button.addEventListener('click', function () {
        var input = document.getElementById(button.getAttribute('aria-controls'));
        var visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.textContent = visible ? 'Sembunyikan' : 'Tampilkan';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Sembunyikan PIN' : 'Tampilkan PIN');
    });
});
</script>
