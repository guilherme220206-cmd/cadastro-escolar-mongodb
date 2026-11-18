</main>

<footer class="text-center text-muted small py-4">
    PHP + MongoDB · Estudantes, Cursos e Matrículas (N:N)
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Pede confirmação antes de enviar formulários de exclusão
    document.querySelectorAll('form[data-confirmar]').forEach(form => {
        form.addEventListener('submit', ev => {
            if (!confirm(form.dataset.confirmar)) ev.preventDefault();
        });
    });
</script>
<?php if (!empty($scriptsExtras)) echo $scriptsExtras; ?>
</body>
</html>
