<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
        </main>
        <footer class="workspace-footer"><?php echo !isset($is_admin) || $is_admin ? 'Panel de administración' : 'Mi cuenta'; ?> · <?php echo html_escape(date('Y')); ?></footer>
    </div>
</div>
</body>
</html>
