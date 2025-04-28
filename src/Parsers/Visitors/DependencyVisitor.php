<?php

namespace drahil\Socraites\Parsers\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class DependencyVisitor extends NodeVisitorAbstract
{
    private array $useStatements = [];
    private array $extendedClasses = [];
    private array $definedClasses = [];
    private array $definedFunctions = [];

    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                $this->useStatements[] = $use->name->toString();
            }
        }

        if ($node instanceof Node\Stmt\Class_) {
            $className = $node->name->toString();
            $this->definedClasses[] = $className;

            if ($node->extends) {
                $this->extendedClasses[$className] = $node->extends->toString();
            }

            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\ClassMethod) {
                    $this->definedFunctions[] = $stmt->name->toString();
                }
            }
        }

        if ($node instanceof Node\Stmt\Function_) {
            $this->definedFunctions[] = $node->name->toString();
        }
    }

    public function getUseStatements(): array
    {
        return $this->useStatements;
    }

    public function getExtendedClasses(): array
    {
        return $this->extendedClasses;
    }

    public function getDefinedClasses(): array
    {
        return $this->definedClasses;
    }

    public function getDefinedFunctions(): array
    {
        return $this->definedFunctions;
    }
}