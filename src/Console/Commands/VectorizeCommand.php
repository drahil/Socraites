<?php

namespace drahil\Socraites\Console\Commands;

use drahil\Socraites\Parsers\FileChunksParser;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VectorizeCommand extends Command
{
    public function __construct()
    {
        parent::__construct('vectorize');
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Vectorize whole codebase');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->getFilesFromAppDirectory();

        $fileChunksParser = new FileChunksParser();
        foreach ($files as $file) {
            try {
                $parsed = $fileChunksParser->parse($file);
                foreach ($parsed['chunks'] as $chunk) {
                    \DB::table('code_chunks')->insert([
                        'type' => $chunk['type'],
                        'method_name' => $chunk['method_name'] ?? '',
                        'class_name' => $chunk['class_name'] ?? '',
                        'namespace' => $chunk['namespace'],
                        'file_path' => $chunk['file_path'],
                        'start_line' => $chunk['start_line'] ?? '1',
                        'end_line' => $chunk['end_line'] ?? '1',
                        'code' => $chunk['code'] ?? json_encode($chunk['imports']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                $output->writeln("<error>Error parsing file {$file}: {$e->getMessage()}</error>");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Get all files from the app directory recursively.
     *
     * @return array
     */
    private function getFilesFromAppDirectory(): array
    {
        $directory = getcwd() . '/app';

        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        $files = [];
        foreach ($rii as $file) {
            if ($file->isFile()) {
                $relativePath = $file->getRealPath();
                $files[] = $relativePath;
            }
        }

        return $files;
    }
}
