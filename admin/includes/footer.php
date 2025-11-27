        </div><!-- /.admin-content -->
    </main><!-- /.admin-main -->
    
    <!-- Admin JS -->
    <script src="<?= asset('js/admin.js') ?>"></script>
    
    <?php if (isset($extraJs)): ?>
        <?php foreach ($extraJs as $js): ?>
            <script src="<?= asset($js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (isset($inlineJs)): ?>
        <script><?= $inlineJs ?></script>
    <?php endif; ?>
</body>
</html>
