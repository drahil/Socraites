<?php

namespace drahil\Socraites\Parsers\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class UsageTrackingVisitor extends NodeVisitorAbstract
{
    protected array $imports = [];
    protected array $usageCounts = [];
    protected array $variableTypes = [];

    public function __construct(array $imports)
    {
        $this->imports = $imports;
        // Initialize usage counts for all imports
        foreach ($imports as $alias => $fullName) {
            $this->usageCounts[$fullName] = 0;
        }
    }

    public function enterNode(Node $node): void
    {
        // Track direct class references (instantiation, static calls)
        if ($node instanceof Node\Expr\New_) {
            if ($node->class instanceof Node\Name) {
                $className = $node->class->toString();
                if (isset($this->imports[$className])) {
                    $this->usageCounts[$this->imports[$className]]++;
                }
            }
        }

        // Track variable assignments with imported classes
        if ($node instanceof Node\Expr\Assign) {
            if ($node->expr instanceof Node\Expr\New_ &&
                $node->expr->class instanceof Node\Name) {

                $className = $node->expr->class->toString();
                if (isset($this->imports[$className])) {
                    $fullClassName = $this->imports[$className];

                    // Track which variable is of which type
                    if ($node->var instanceof Node\Expr\Variable) {
                        $varName = $node->var->name;
                        $this->variableTypes[$varName] = $fullClassName;
                    } elseif ($node->var instanceof Node\Expr\PropertyFetch) {
                        // Handle $this->property = new SomeClass()
                        if ($node->var->var instanceof Node\Expr\Variable &&
                            $node->var->var->name === 'this') {
                            $propName = '$this->' . $node->var->name->name;
                            $this->variableTypes[$propName] = $fullClassName;
                        }
                    }

                    $this->usageCounts[$fullClassName]++;
                }
            }
        }

        // Track method calls on variables of imported types
        if ($node instanceof Node\Expr\MethodCall) {
            if ($node->var instanceof Node\Expr\Variable) {
                $varName = $node->var->name;
                if (isset($this->variableTypes[$varName])) {
                    $this->usageCounts[$this->variableTypes[$varName]]++;
                }
            } elseif ($node->var instanceof Node\Expr\PropertyFetch) {
                // Handle $this->property->method()
                if ($node->var->var instanceof Node\Expr\Variable &&
                    $node->var->var->name === 'this') {
                    $propName = '$this->' . $node->var->name->name;
                    if (isset($this->variableTypes[$propName])) {
                        $this->usageCounts[$this->variableTypes[$propName]]++;
                    }
                }
            }
        }

        // Track static method calls on imported classes
        if ($node instanceof Node\Expr\StaticCall) {
            if ($node->class instanceof Node\Name) {
                $className = $node->class->toString();
                if (isset($this->imports[$className])) {
                    $this->usageCounts[$this->imports[$className]]++;
                }
            }
        }
    }

    public function getUsageCounts(): array
    {
        return $this->usageCounts;
    }
}
