<?php
/**
 * Footer commun pour toutes les pages admin
 */

if (!defined('TOOLBOX_INTERNAL')) {
    die('Accès direct interdit');
}
?>
            </div> <!-- main-body -->
        </main>
    </div> <!-- admin-layout -->
    
    <!-- Admin JavaScript -->
    <script src="js/admin.js"></script>
    
    <?php if (isset($extra_scripts)) echo $extra_scripts; ?>
</body>
</html>