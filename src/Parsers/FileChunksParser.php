<?php

declare(strict_types=1);

namespace drahil\Socraites\Parsers;

use drahil\Socraites\Parsers\Visitors\MethodChunkVisitor;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class FileChunksParser
{
    private Parser $parser;
    private NodeTraverser $traverser;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
        $this->traverser = new NodeTraverser();
    }

    /**
     * Parses a PHP file and extracts method chunks.
     *
     * @param string $filePath
     * @return array
     */
    public function parse(string $filePath): array
    {
        $code = file_get_contents($filePath);
        $fileLines = file($filePath);
        $ast = $this->parser->parse($code);

        $methodVisitor = new MethodChunkVisitor(
            $filePath,
            [],
            '',
            '',
            $fileLines
        );

        $this->traverser->addVisitor($methodVisitor);
        $this->traverser->traverse($ast);

        return [
            'chunks' => $methodVisitor->getChunks(),
        ];
    }
}
