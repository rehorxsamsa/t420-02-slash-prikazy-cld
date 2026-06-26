<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Model\Task;
use App\Repository\TaskRepositoryInterface;

/**
 * In-memory fake repository — žádná DB, ideální pro rychlé jednotkové testy.
 */
final class InMemoryTaskRepository implements TaskRepositoryInterface
{
    /** @var array<int, Task> */
    private array $store = [];
    private int $nextId = 1;

    /** @param list<array{title: string, done: bool}> $initial */
    public function __construct(array $initial = [])
    {
        foreach ($initial as $row) {
            $id = $this->nextId++;
            $this->store[$id] = new Task($id, $row['title'], $row['done'], date('c'));
        }
    }

    public function all(): array
    {
        return array_values($this->store);
    }

    public function find(int $id): ?Task
    {
        return $this->store[$id] ?? null;
    }

    public function create(string $title): Task
    {
        $id = $this->nextId++;
        $task = new Task($id, $title, false, date('c'));
        $this->store[$id] = $task;

        return $task;
    }

    public function toggle(int $id): void
    {
        if (!isset($this->store[$id])) {
            return;
        }
        $old = $this->store[$id];
        $this->store[$id] = new Task($old->id, $old->title, !$old->done, $old->createdAt);
    }

    public function delete(int $id): void
    {
        unset($this->store[$id]);
    }
}
