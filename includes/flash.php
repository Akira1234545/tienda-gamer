<?php foreach (flash_messages() as $message): ?>
    <div class="alert alert-<?php echo e($message['type']); ?> alert-dismissible fade show" role="alert">
        <?php echo e($message['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endforeach; ?>
