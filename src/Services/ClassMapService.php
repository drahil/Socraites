<?php

namespace drahil\Socraites\Services;

use Composer\Autoload\ClassLoader;
use RuntimeException;

class ClassMapService
{
    private ClassLoader $classLoader;

    public function __construct()
    {
        $this->classLoader = $this->resolveComposerClassLoader();
    }

    /**
     * Get the file path for a given class name.
     *
     * @param string $className The fully qualified class name.
     * @return string|null The file path or null if not found.
     */
    public function getFilePathForClass(string $className): ?string
    {
        return $this->classLoader->findFile($className) ?: null;
    }

    /**
     * Resolve the Composer ClassLoader instance.
     *
     * @return ClassLoader The class name or null if not found.
     */
    private function resolveComposerClassLoader(): ClassLoader
    {
        foreach (spl_autoload_functions() as $loader) {
            if (is_array($loader) && $loader[0] instanceof ClassLoader) {
                return $loader[0];
            }
        }

        throw new RuntimeException('Composer ClassLoader not found.');
    }
}
