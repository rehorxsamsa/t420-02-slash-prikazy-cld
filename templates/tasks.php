<?php
/**
 * @var list<\App\Model\Task> $tasks
 * @var int $progress
 */
declare(strict_types=1);

$e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>t420-02-slash-prikazy-cld — Seznam úkolů</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">
</head>
<body class="bg-body-tertiary">
    <main class="container py-4" style="max-width: 640px;">
        <header class="text-secondary text-uppercase small mb-2" style="letter-spacing: .05em;">
            t420-02-slash-prikazy-cld
        </header>
        <h1 class="h3 mb-3">Seznam úkolů</h1>

        <div class="d-flex align-items-center gap-2 mb-4">
            <div class="progress flex-grow-1" role="progressbar"
                 aria-label="Hotovo" aria-valuenow="<?= $progress ?>"
                 aria-valuemin="0" aria-valuemax="100" style="height: 1.25rem;">
                <div class="progress-bar" style="width: <?= $progress ?>%;"><?= $progress ?> %</div>
            </div>
        </div>

        <form class="input-group mb-4" method="post" action="/tasks">
            <input type="text" name="title" class="form-control" placeholder="Nový úkol…" autofocus>
            <button type="submit" class="btn btn-primary">Přidat</button>
        </form>

        <?php if ($tasks === []): ?>
            <p class="text-secondary">Zatím žádné úkoly.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($tasks as $task): ?>
                    <li class="list-group-item d-flex align-items-center gap-3">
                        <form class="m-0" method="post" action="/tasks/<?= (int) $task->id ?>/toggle">
                            <button type="submit"
                                    class="btn btn-sm <?= $task->done ? 'btn-outline-secondary' : 'btn-outline-success' ?>"
                                    title="<?= $task->done ? 'Vrátit zpět' : 'Hotovo' ?>">
                                <?= $task->done ? '↩︎' : '✓' ?>
                            </button>
                        </form>
                        <span class="flex-grow-1 <?= $task->done ? 'text-decoration-line-through text-secondary' : '' ?>">
                            <?= $e($task->title) ?>
                        </span>
                        <form class="m-0" method="post" action="/tasks/<?= (int) $task->id ?>/delete">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Smazat">✕</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </main>
</body>
</html>
