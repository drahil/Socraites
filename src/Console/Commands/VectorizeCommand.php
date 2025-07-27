<?php

declare(strict_types=1);

namespace drahil\Socraites\Console\Commands;

use drahil\Socraites\Parsers\FileChunksParser;
use drahil\Socraites\Services\AiService;
use GuzzleHttp\Exception\GuzzleException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VectorizeCommand extends Command
{
    protected AiService $aiService;

    public function __construct()
    {
        parent::__construct('vectorize');

        $this->aiService = new AiService(
            config('socraites.openai_api_key')
        );
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
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->getFilesFromAppDirectory();

        $fileChunksParser = new FileChunksParser();
        foreach ($files as $file) {
            try {
                $output->writeln("<info>Processing file: {$file}</info>");
                $parsed = $fileChunksParser->parse($file);

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
}
