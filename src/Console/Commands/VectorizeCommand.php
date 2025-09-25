<?php

declare(strict_types=1);

namespace drahil\Socraites\Console\Commands;

use drahil\Socraites\Parsers\FileChunksParser;
use drahil\Socraites\Services\AiService;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class VectorizeCommand extends Command
{
    protected AiService $aiService;

    public function __construct()
    {
        parent::__construct('socraites:vectorize');

        $this->aiService = new AiService(
            config('socraites.openai_api_key')
        );
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Vectorize whole codebase')
            ->addOption(
                'files',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Select files to vectorize by providing their paths. If not provided, all files in the app directory will be processed.',
                []
            )->addOption(
                'directory',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify a directory to vectorize files from. If not provided, the app directory will be used.',
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->getFiles($input);

        $fileChunksParser = new FileChunksParser();
        foreach ($files as $file) {
            try {
                $output->writeln("<info>Processing file: {$file}</info>");
                $parsed = $fileChunksParser->parse($file);

                \DB::table('code_chunks')
                    ->where('file_path', $file)
                    ->delete();

                $this->handleChunks($parsed['chunks']);
            } catch (\Exception $e) {
                $output->writeln("<error>Error parsing file {$file}: {$e->getMessage()}</error>");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Get all files from the app directory recursively.
     *
     * @param string $path
     * @return array
     */
    private function getFilesFromDirectory(string $path): array
    {
        $directory = getcwd() . $path;

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

    /**
     * Handle the chunks of code and insert them into the database.
     *
     * @param array $chunks
     * @return void
     * @throws GuzzleException
     */
    private function handleChunks(array $chunks): void
    {
        foreach ($chunks as $chunk) {
            $code = $chunk['code'] ?? json_encode($chunk['imports']);

            $codeChunkId = \DB::table('code_chunks')->insertGetId([
                'type' => $chunk['type'],
                'method_name' => $chunk['method_name'] ?? '',
                'class_name' => $chunk['class_name'] ?? '',
                'namespace' => $chunk['namespace'],
                'file_path' => $chunk['file_path'],
                'start_line' => $chunk['start_line'] ?? '-1',
                'end_line' => $chunk['end_line'] ?? '-1',
                'code' => $code,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->createEmbeddings($codeChunkId, $code);
        }
    }

    /**
     * Create embeddings for the given code chunk.
     *
     * @param int $codeChunkId
     * @param string $code
     * @return void
     * @throws GuzzleException
     */
    private function createEmbeddings(int $codeChunkId, string $code): void
    {
        $embedding = $this->aiService
            ->buildPayload()
            ->usingModel('text-embedding-3-small')
            ->withInput($code)
            ->getEmbedding();

        \DB::table('code_chunks')
            ->where('id', $codeChunkId)
            ->update(['embedding' => $embedding]);
    }

    /**
     * Get files based on the input options.
     *
     * @param InputInterface $input
     * @return array
     * @throws InvalidArgumentException
     */
    private function getFiles(InputInterface $input): array
    {
        $files = $input->getOption('files');
        $directory = $input->getOption('directory');

        if ($files && $directory) {
            throw new InvalidArgumentException('You cannot use both --files and --directory options at the same time.');
        }

        if ($files) {
            // before returning we need to add the full path to each file
            return array_map(function ($file) {
                return getcwd() . '/' . ltrim($file, '/');
            }, $files);
        }

        if ($directory) {
            return $this->getFilesFromDirectory($directory);
        }

        return $this->getFilesFromDirectory('/app');
    }
}
