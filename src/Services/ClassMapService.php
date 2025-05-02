<?php

namespace drahil\Socraites\Services;

use Composer\Autoload\ClassLoader;

class ClassMapService
{
    private ClassLoader $classLoader;

    public function __construct()
    {
        $this->classLoader = $this->resolveComposerClassLoader();
    }

    public function getFilePathForClass(string $className): ?string
    {
        return $this->classLoader->findFile($className) ?: null;
    }

    private function resolveComposerClassLoader(): ClassLoader
    {
        foreach (spl_autoload_functions() as $loader) {
            if (is_array($loader) && $loader[0] instanceof ClassLoader) {
                return $loader[0];
            }
        }

        throw new \RuntimeException('Composer ClassLoader not found.');
    }
}
