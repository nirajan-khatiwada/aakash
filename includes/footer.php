    <footer>
        <p>&copy; <?php echo date("Y"); ?> LibraryMS</p>
    </footer>

    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>