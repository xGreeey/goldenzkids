<?php
declare(strict_types=1);

$compact = !empty($accountabilityCompact);
?>
<section class="card-panel accountability-panel<?= $compact ? ' accountability-panel--compact' : '' ?>">
    <h2 class="panel-title"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i> Accountability policy</h2>
    <p class="accountability-lead">
        Portal accounts are controlled centrally. No employee can hide or privately change their login credentials—every action leaves a permanent record.
    </p>
    <ul class="accountability-rules">
        <?php foreach (superadmin_accountability_rules() as $rule): ?>
            <li><i class="fa-solid fa-check" aria-hidden="true"></i><?= e($rule) ?></li>
        <?php endforeach; ?>
    </ul>
</section>
