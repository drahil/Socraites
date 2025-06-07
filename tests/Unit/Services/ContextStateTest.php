<?php

use drahil\Socraites\Services\ContextState;

describe('ContextState', function () {
    beforeEach(function () {
        $this->state = new ContextState();
    });

    it('initializes with empty state', function () {
        expect($this->state->context)->toBeEmpty()
            ->and($this->state->processedFiles)->toBeEmpty()
            ->and($this->state->totalSize)->toBe(0);
    });

    it('can add content to context', function () {
        $this->state->addToContext('test.php', 'hello');

        expect($this->state->context)->toHaveKey('test.php')
            ->and($this->state->context['test.php'])->toBe('hello')
            ->and($this->state->processedFiles)->toHaveKey('test.php')
            ->and($this->state->processedFiles['test.php'])->toBeTrue()
            ->and($this->state->totalSize)->toBe(5);
    });

    it('increments total size when adding content', function () {
        $initialSize = $this->state->totalSize;

        $this->state->addToContext('file1.php', 'short');
        $this->state->addToContext('file2.php', 'longer content');

        expect($this->state->totalSize)->toBe($initialSize + 5 + 14);
    });

    it('can be initialized with existing data', function () {
        $preloaded = new ContextState(
            context: ['existing.php' => 'content'],
            processedFiles: ['existing.php' => true],
            totalSize: 7
        );

        expect($preloaded->context)->toHaveKey('existing.php')
            ->and($preloaded->totalSize)->toBe(7);
    });
});
