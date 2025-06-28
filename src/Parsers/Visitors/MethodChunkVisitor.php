<?php

declare(strict_types=1);

namespace drahil\Socraites\Parsers\Visitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;

class MethodChunkVisitor extends NodeVisitorAbstract
{
    public function __construct(
        protected string $filePath,
        protected array  $chunks = [],
        protected string $namespace = '',
        protected string $className = '',
        protected array  $fileLines = [],
        protected array  $imports = [],
    )
    {}

    /**
     * This method is called when the visitor enters a node.
     * It is used to collect information about the node.
     *
     * @param Node $node
     * @return int|null|Node|Node[]
     */
    public function enterNode(Node $node): int|Node|array|null
    {
        if ($node instanceof Namespace_) {
            $this->namespace = $node->name->toString();
        }

        if ($node instanceof \PhpParser\Node\Stmt\Class_) {
            $this->className = $node->name->toString();
        }

        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                $this->imports[] = [
                    'class' => $use->name->toString(),
                    'alias' => $use->alias ? $use->alias->toString() : null,
                    'line' => $node->getStartLine(),
                ];
            }
        }

        if ($node instanceof ClassMethod) {
            $startLine = $node->getStartLine();
            $endLine = $node->getEndLine();

            $methodCode = implode('', array_slice(
                $this->fileLines,
                $startLine - 1,
                $endLine - $startLine + 1
            ));

            $this->chunks[] = [
                'type' => 'method',
                'method_name' => $node->name->toString(),
                'class_name' => $this->className,
                'namespace' => $this->namespace,
                'file_path' => $this->filePath,
                'start_line' => $startLine,
                'end_line' => $endLine,
                'code' => ($node->getDocComment() ? $node->getDocComment()->getText() . "\n" : '') . $methodCode,
            ];
        }

        return null;
    }

    /**
     * This method is called after the traversal of the nodes is complete.
     * It is used to finalize the visitor state.
     *
     * @param array $nodes
     * @return array|null
     */
    public function afterTraverse(array $nodes): array|null
    {
        if (! empty($this->imports)) {
            $this->chunks[] = [
                'type' => 'imports',
                'file_path' => $this->filePath,
                'namespace' => $this->namespace,
                'imports' => $this->imports,
            ];
        }

        if (empty($this->chunks)) {
            $this->chunks[] = [
                'type' => 'empty',
                'file_path' => $this->filePath,
                'namespace' => $this->namespace,
                'class_name' => $this->className,
                'imports' => ''
            ];
        }

        return null;
    }

    /**
     * Get the collected method chunks.
     *
     * @return array
     */
    public function getChunks(): array
    {
        return $this->chunks;
    }
}
