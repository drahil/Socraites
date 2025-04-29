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

    public function parse(string $filePath): array
    {
        $code = file_get_contents($filePath);
        $ast = $this->parser->parse($code);

        $dependencyVisitor = new DependencyVisitor();
        $this->traverser->addVisitor($dependencyVisitor);

        $this->traverser->traverse($ast);
        $this->traverser->removeVisitor($dependencyVisitor);

        $imports = $dependencyVisitor->getUseStatements();

        $usageVisitor = new UsageTrackingVisitor($imports);
        $this->traverser->addVisitor($usageVisitor);
        $this->traverser->traverse($ast);

        return [
            'imports' => $imports,
            'extends' => $dependencyVisitor->getExtendedClasses(),
            'classes' => $dependencyVisitor->getDefinedClasses(),
            'functions' => $dependencyVisitor->getDefinedFunctions(),
            'usageCounts' => $usageVisitor->getUsageCounts(),
        ];
    }
}
