<?php

namespace drahil\Socraites\Parsers;

use drahil\Socraites\Parsers\Visitors\DependencyVisitor;
use drahil\Socraites\Parsers\Visitors\UsageTrackingVisitor;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class FileParser
{
    private Parser $parser;
    private NodeTraverser $traverser;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new NameResolver());
    }

    /**
     * Parses a PHP file and collects information about its dependencies, defined classes, functions, and usage counts.
     *
     * @param string $filePath The path to the PHP file to parse.
     * @return array An associative array containing:
     *               - 'imports': An array of imported classes.
     *               - 'extends': An array of extended classes.
     *               - 'classes': An array of defined classes.
     *               - 'functions': An array of defined functions.
     *               - 'usageCounts': An array of usage counts for each class.
     */
    public function parse(string $filePath): array
    {
        $code = file_get_contents($filePath);
        $ast = $this->parser->parse($code);

        $dependencyVisitor = new DependencyVisitor();
        $this->traverser->addVisitor($dependencyVisitor);
        $this->traverser->traverse($ast);
        $this->traverser->removeVisitor($dependencyVisitor);

        $imports = $dependencyVisitor->getUseStatements();

        $usageVisitor = new UsageTrackingVisitor($imports, $filePath);
        $this->traverser->addVisitor($usageVisitor);
        $this->traverser->traverse($ast);
        $this->traverser->removeVisitor($usageVisitor);

        return [
            'imports' => $imports,
            'extends' => $dependencyVisitor->getExtendedClasses(),
            'classes' => $dependencyVisitor->getDefinedClasses(),
            'functions' => $dependencyVisitor->getDefinedFunctions(),
            'usageCounts' => $usageVisitor->getFileUsageCounts(),
        ];
    }
}
