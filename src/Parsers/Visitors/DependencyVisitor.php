<?php

declare(strict_types=1);

namespace drahil\Socraites\Parsers\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class DependencyVisitor extends NodeVisitorAbstract
{
    private array $useStatements = [];
    private array $extendedClasses = [];
    private array $definedClasses = [];
    private array $definedFunctions = [];

    /**
     * This method is called when the visitor enters a node.
     * It is used to collect information about the node.
     *
     * @param Node $node
     * @return int|null|Node|Node[]
     */
    public function enterNode(Node $node): int|Node|array|null
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

        return null;
    }

    /**
     * Get the use statements collected by the visitor.
     *
     * @return array
     */
    public function getUseStatements(): array
    {
        return $this->useStatements;
    }

    /**
     * Get the extended classes collected by the visitor.
     *
     * @return array
     */
    public function getExtendedClasses(): array
    {
        return $this->extendedClasses;
    }

    /**
     * Get the defined classes collected by the visitor.
     *
     * @return array
     */
    public function getDefinedClasses(): array
    {
        return $this->definedClasses;
    }

    /**
     * Get the defined functions collected by the visitor.
     *
     * @return array
     */
    public function getDefinedFunctions(): array
    {
        return $this->definedFunctions;
    }
}
