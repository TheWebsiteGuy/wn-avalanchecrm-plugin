<div class="kanban-toolbar" style="margin-bottom: 20px; padding: 0 20px;">
    <button type="button" data-control="popup" data-handler="onLoadTaskForm" class="btn btn-primary oc-icon-plus">
        New Task
    </button>
</div>

<div class="kanban-board-standalone" id="tasks-kanban-board-container">
    <?= $this->makePartial('kanban_board') ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    $(document).ready(initStandaloneKanban);
    $(document).on('ajaxUpdate', initStandaloneKanban);

    function initStandaloneKanban() {
        const columns = document.querySelectorAll('.kanban-tasks');

        columns.forEach(column => {
            if (column.classList.contains('sortable-initialized')) return;
            column.classList.add('sortable-initialized');

            new Sortable(column, {
                group: 'kanban',
                animation: 150,
                ghostClass: 'ghost',
                onEnd: function (evt) {
                    const taskId = evt.item.getAttribute('data-id');
                    const newStatus = evt.to.getAttribute('data-status');
                    const order = Array.from(evt.to.children).map(el => el.getAttribute('data-id'));

                    $.request('onUpdateTaskStatus', {
                        data: {
                            task_id: taskId,
                            status: newStatus,
                            order: order
                        },
                        flash: true
                    });
                }
            });
        });
    }
</script>

<style>
    .kanban-board-standalone {
        display: flex;
        overflow-x: auto;
        padding: 20px;
        background: #f4f7f6;
        min-height: calc(100vh - 150px);
    }

    .kanban-row {
        display: flex;
        gap: 20px;
        width: 100%;
    }

    .kanban-column {
        flex: 1;
        min-width: 300px;
        background: #ebedef;
        border-radius: 8px;
        padding: 15px;
        display: flex;
        flex-direction: column;
    }

    .kanban-column h3 {
        margin: 0 0 15px 0;
        font-size: 16px;
        font-weight: 600;
        color: #444;
        text-transform: uppercase;
    }

    .kanban-tasks {
        flex: 1;
        min-height: 100px;
    }

    .kanban-task {
        background: #fff;
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        position: relative;
        cursor: pointer;
        border-left: 4px solid #ddd;
    }

    .kanban-task:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .ghost {
        opacity: 0.5;
        background: #c8ebfb;
    }

    .task-priority.priority-low {
        border-left-color: #5bc0de;
    }

    .task-priority.priority-medium {
        border-left-color: #f0ad4e;
    }

    .task-priority.priority-high {
        border-left-color: #d9534f;
    }

    .task-priority.priority-urgent {
        border-left-color: #000;
    }

    .kanban-task h4 {
        margin: 0 0 5px 0;
        font-size: 14px;
        font-weight: 600;
    }

    .kanban-task p {
        margin: 0;
        font-size: 12px;
        color: #777;
    }

    .task-footer {
        margin-top: 10px;
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        color: #999;
    }

    .task-edit {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 10px;
        color: #3498db;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .kanban-task:hover .task-edit {
        opacity: 1;
    }
</style>