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
    <style>
        body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; }
        h1 { margin-bottom: .25rem; }
        header.app { color: #888; font-size: .85rem; letter-spacing: .05em; margin-bottom: 1rem; }
        .progress { color: #555; margin-bottom: 1.5rem; }
        ul { list-style: none; padding: 0; }
        li { display: flex; align-items: center; gap: .75rem; padding: .5rem 0; border-bottom: 1px solid #eee; }
        li.done span { text-decoration: line-through; color: #999; }
        .title { flex: 1; }
        form.inline { display: inline; margin: 0; }
        button { cursor: pointer; }
        form.add { display: flex; gap: .5rem; margin-bottom: 1.5rem; }
        form.add input { flex: 1; padding: .5rem; }
    </style>
</head>
<body>
    <header class="app">t420-02-slash-prikazy-cld</header>
    <h1>Seznam úkolů</h1>
    <p class="progress">Hotovo: <?= $progress ?> %</p>

    <form class="add" method="post" action="/tasks">
        <input type="text" name="title" placeholder="Nový úkol…" autofocus>
        <button type="submit">Přidat</button>
    </form>

    <ul>
        <?php foreach ($tasks as $task): ?>
            <li class="<?= $task->done ? 'done' : '' ?>">
                <form class="inline" method="post" action="/tasks/<?= (int) $task->id ?>/toggle">
                    <button type="submit"><?= $task->done ? '↩︎' : '✓' ?></button>
                </form>
                <span class="title"><?= $e($task->title) ?></span>
                <form class="inline" method="post" action="/tasks/<?= (int) $task->id ?>/delete">
                    <button type="submit">✕</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($tasks === []): ?>
        <p>Zatím žádné úkoly.</p>
    <?php endif; ?>
</body>
</html>
