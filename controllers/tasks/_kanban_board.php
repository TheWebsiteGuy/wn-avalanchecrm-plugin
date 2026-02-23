<?php
$statuses = (new \TheWebsiteGuy\NexusCRM\Models\Task)->getStatusOptions();
?>

<div class="kanban-row">
    <?php foreach ($statuses as $code => $label): ?>
        <div class="kanban-column">
            <h3>
                <?= e($label) ?>
            </h3>

            <div class="kanban-tasks" data-status="<?= $code ?>" id="tasks-<?= $code ?>">
                <?php if (isset($tasks[$code])): ?>
                    <?php foreach ($tasks[$code] as $task): ?>
                        <div class="kanban-task" data-id="<?= $task->id ?>" data-control="popup" data-handler="onLoadTaskForm"
                            data-request-data="task_id: <?= $task->id ?>">
                            <div class="task-priority priority-<?= $task->priority ?>"></div>
                            <div class="task-content">
                                <h4>
                                    <?= e($task->title) ?>
                                </h4>
                                <p>
                                    <?= e($task->project ? $task->project->name : 'No Project') ?>
                                </p>
                                <div class="task-footer">
                                    <span class="user">
                                        <?= e($task->assigned_to ? $task->assigned_to->login : 'Unassigned') ?>
                                    </span>
                                    <?php if ($task->due_date && $task->due_date instanceof \DateTimeInterface): ?>
                                        <span class="date">
                                            <?= $task->due_date->format('M d') ?>
                                        </span>
                                    <?php endif ?>
                                </div>
                            </div>
                            <a href="javascript:;" data-control="popup" data-handler="onLoadTaskForm"
                                data-request-data="task_id: <?= $task->id ?>" class="task-edit">Edit</a>
                        </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>