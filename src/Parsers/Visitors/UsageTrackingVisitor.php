<?php

namespace drahil\Socraites\Parsers\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Name;

class UsageTrackingVisitor extends NodeVisitorAbstract
{
    protected array $imports = [];
    protected array $usageCounts = [];
    protected array $fileUsageCounts = [];
    protected array $variableTypes = [];
    protected string $currentNamespace = '';
    protected array $aliases = [];
    protected string $currentFile = '';

    public function __construct(array $imports, string $filePath = '')
    {
        $this->imports = $imports;
        $this->currentFile = $filePath;

        // Create a map of short names to FQCNs
        foreach ($imports as $alias => $fullName) {
            $shortName = $this->getShortName($fullName);
            $this->aliases[$shortName] = $fullName;
            $this->usageCounts[$fullName] = 0;

            if ($filePath) {
                $this->fileUsageCounts[$filePath][$fullName] = 0;
            }
        }
    }

    public function setCurrentFile(string $filePath): void
    {
        $this->currentFile = $filePath;

        if (!isset($this->fileUsageCounts[$filePath])) {
            foreach ($this->imports as $fullName) {
                $this->fileUsageCounts[$filePath][$fullName] = 0;
            }
        }
    }

    private function getShortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);
        return end($parts);
    }

    private function resolveClassName(Name $name): ?string
    {
        $className = $name->toString();

        // Check for fully qualified name
        if (str_starts_with($className, '\\')) {
            return substr($className, 1);
        }

        // Check direct imports
        if (isset($this->imports[$className])) {
            return $this->imports[$className];
        }

        // Check aliases (short names)
        $shortName = $this->getShortName($className);
        if (isset($this->aliases[$shortName])) {
            return $this->aliases[$shortName];
        }

        // Check if it's in current namespace
        if ($this->currentNamespace) {
            $potentialFqcn = $this->currentNamespace . '\\' . $className;
            if (isset($this->aliases[$this->getShortName($potentialFqcn)])) {
                return $potentialFqcn;
            }
        }

        return null;
    }

    public function beforeTraverse(array $nodes)
    {
        $this->currentNamespace = '';
        $this->variableTypes = [];
        return null;
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentNamespace = $node->name ? $node->name->toString() : '';
            return;
        }

        $this->trackVariableAssignments($node);
        $this->trackMethodCalls($node);
        $this->trackStaticCalls($node);
        $this->trackNewInstances($node);
        $this->trackTypeHints($node);
    }

    protected function incrementUsageCount(string $fqcn): void
    {
        $this->usageCounts[$fqcn]++;

        if ($this->currentFile) {
            $this->fileUsageCounts[$this->currentFile][$fqcn]++;
        }
    }

    protected function trackVariableAssignments(Node $node): void
    {
        if (!$node instanceof Node\Expr\Assign) {
            return;
        }

        if ($node->expr instanceof Node\Expr\New_ && $node->expr->class instanceof Name) {
            $fqcn = $this->resolveClassName($node->expr->class);
            if ($fqcn) {
                $this->incrementUsageCount($fqcn);

                if ($node->var instanceof Node\Expr\Variable) {
                    $this->variableTypes['$' . $node->var->name] = $fqcn;
                } elseif ($node->var instanceof Node\Expr\PropertyFetch) {
                    if ($node->var->var instanceof Node\Expr\Variable && $node->var->var->name === 'this') {
                        $this->variableTypes['$this->' . $node->var->name->name] = $fqcn;
                    }
                }
            }
        }
    }

    protected function trackMethodCalls(Node $node): void
    {
        if (!$node instanceof Node\Expr\MethodCall) {
            return;
        }

        $var = $node->var;
        $varName = null;

        if ($var instanceof Node\Expr\Variable) {
            $varName = '$' . $var->name;
        } elseif ($var instanceof Node\Expr\PropertyFetch) {
            if ($var->var instanceof Node\Expr\Variable && $var->var->name === 'this') {
                $varName = '$this->' . $var->name->name;
            }
        }

        if ($varName && isset($this->variableTypes[$varName])) {
            $this->incrementUsageCount($this->variableTypes[$varName]);
        }
    }

    protected function trackStaticCalls(Node $node): void
    {
        if ($node instanceof Node\Expr\StaticCall && $node->class instanceof Name) {
            $fqcn = $this->resolveClassName($node->class);
            if ($fqcn) {
                $this->incrementUsageCount($fqcn);
            }
        }
    }

    protected function trackNewInstances(Node $node): void
    {
        if ($node instanceof Node\Expr\New_ && $node->class instanceof Name) {
            $fqcn = $this->resolveClassName($node->class);
            if ($fqcn) {
                $this->incrementUsageCount($fqcn);
            }
        }
    }

    protected function trackTypeHints(Node $node): void
    {
        if ($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
            foreach ($node->params as $param) {
                if ($param->type instanceof Name) {
                    $fqcn = $this->resolveClassName($param->type);
                    if ($fqcn) {
                        $this->incrementUsageCount($fqcn);
                    }
                }
            }

            if ($node->returnType instanceof Name) {
                $fqcn = $this->resolveClassName($node->returnType);
                if ($fqcn) {
                    $this->incrementUsageCount($fqcn);
                }
            }
        }
    }

    public function getFileUsageCounts(): array
    {
        $result = [];
        foreach ($this->fileUsageCounts as $file => $counts) {
            $result[$file] = array_filter($counts, fn($count) => $count > 0);
        }
        return $result;
    }

    public function getUsageCountsForFile(string $filePath): array
    {
        return $this->fileUsageCounts[$filePath] ?? [];
    }
}
