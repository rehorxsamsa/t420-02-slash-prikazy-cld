<?php

declare(strict_types=1);

use App\Service\TaskService;
use Tests\Fake\InMemoryTaskRepository;

/**
 * Testy business logiky TaskService — izolovaně, bez databáze.
 * @return array<string, callable>
 */
return [
    'add() vytvoří úkol' => static function (): void {
        $service = new TaskService(new InMemoryTaskRepository());
        $task = $service->add('Nový úkol');

        assert_same('Nový úkol', $task->title);
        assert_true(!$task->done, 'nový úkol nemá být hotový');
        assert_same(1, count($service->list()));
    },

    'add() ořízne mezery' => static function (): void {
        $service = new TaskService(new InMemoryTaskRepository());
        $task = $service->add('   Úkol s mezerami   ');

        assert_same('Úkol s mezerami', $task->title);
    },

    'add() odmítne prázdný název' => static function (): void {
        $service = new TaskService(new InMemoryTaskRepository());

        assert_throws(
            InvalidArgumentException::class,
            static fn () => $service->add('   '),
            'prázdný název měl vyhodit výjimku'
        );
    },

    'toggle() přepne stav' => static function (): void {
        $repo = new InMemoryTaskRepository([['title' => 'A', 'done' => false]]);
        $service = new TaskService($repo);

        $service->toggle(1);
        assert_true($repo->find(1)?->done === true, 'po toggle má být hotový');

        $service->toggle(1);
        assert_true($repo->find(1)?->done === false, 'po druhém toggle má být nehotový');
    },

    'progress() spočítá procenta' => static function (): void {
        $repo = new InMemoryTaskRepository([
            ['title' => 'A', 'done' => true],
            ['title' => 'B', 'done' => true],
            ['title' => 'C', 'done' => false],
            ['title' => 'D', 'done' => false],
        ]);
        $service = new TaskService($repo);

        assert_same(50, $service->progress());
    },

    'progress() vrátí 0 pro prázdný seznam' => static function (): void {
        $service = new TaskService(new InMemoryTaskRepository());

        assert_same(0, $service->progress());
    },
];
