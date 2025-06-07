<?php

namespace drahil\Socraites\Console\Formatters;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class OutputFormatter
{
    private ConsoleOutput $output;
    private string $border = '─';
    private array $icons = [
        'summary' => '✅',
        'files' => '📁',
        'reviewing' => '🔍',
        'major_issues' => '❌',
        'minor_issues' => '⚠️',
        'suggestions' => '💡',
        'commit' => '💬',
    ];

    public function __construct(protected array $review)
    {
        $this->output = new ConsoleOutput();
        $this->configureStyles();
    }

    /**
     * Set the review data to be printed.
     *
     * @param array $review The review data.
     */
    public function setReview(array $review): void
    {
        $this->review = $review;
    }

    /**
     * Configure the output styles for the console.
     */
    private function configureStyles(): void
    {
        $formatter = $this->output->getFormatter();

        $formatter->setStyle('title', new OutputFormatterStyle('green', null, ['bold']));
        $formatter->setStyle('file', new OutputFormatterStyle('blue', null, ['bold']));
        $formatter->setStyle('major', new OutputFormatterStyle('red', null, ['bold']));
        $formatter->setStyle('minor', new OutputFormatterStyle('yellow', null, ['bold']));
        $formatter->setStyle('suggestion', new OutputFormatterStyle('cyan', null, ['bold']));
        $formatter->setStyle('commit', new OutputFormatterStyle('green', null, ['bold']));
        $formatter->setStyle('border', new OutputFormatterStyle('white', null, []));
        $formatter->setStyle('content', new OutputFormatterStyle('white', null, []));
    }

    /**
     * Print the formatted output to the console.
     */
    public function print(): void
    {
        $this->printHeader();
        $this->printOverallSummary();
        $this->printContextFiles();
        $this->printFileReviews();
        $this->printCommitMessage();
    }

    /**
     * Print the header for the output.
     */
    private function printHeader(): void
    {
        $this->output->writeln('');
        $this->output->writeln('  <options=bold;fg=blue>SOCRAITES CODE REVIEW</>');
        $this->output->writeln('');
    }

    /**
     * Print the overall summary of the code review.
     */
    private function printOverallSummary(): void
    {
        $summary = $this->review['overall_summary'] ?? '';

        $this->printSectionHeader('summary', 'Overall Summary');
        $this->printIndented($summary);
        $this->printSectionFooter();
    }

    /**
     * Print the files from the context of the code review.
     */
    private function printContextFiles(): void
    {
        $contextFiles = $this->review['context'] ?? [];

        $this->printSectionHeader('files', 'Files from context');

        foreach ($contextFiles as $file) {
            $this->output->writeln("     <file>{$file}</>");
        }

        $this->printSectionFooter();
    }

    /**
     * Print the reviews for each file in the code review.
     */
    private function printFileReviews(): void
    {
        $filesOutputs = $this->review['files'] ?? [];
        foreach ($filesOutputs as $block) {
            $this->renderFileReviewBlock($block);
        }
    }

    /**
     * Print the suggested commit message for the code review.
     */
    private function printCommitMessage(): void
    {
        $commitMessage = $this->review['commit_message'] ?? '';

        $this->printSectionHeader('commit', 'Suggested Commit Message');
        $this->printIndented($commitMessage);
        $this->printSectionFooter(true); // Last section
    }

    /**
     * Render a block of file review information.
     *
     * @param array $block The block containing file review data.
     */
    private function renderFileReviewBlock(array $block): void
    {
        $fileName = $block['name'] ?? '';

        $this->output->writeln('');
        $this->output->writeln("  {$this->icons['reviewing']} Reviewing: <file>{$fileName}</>");
        $this->output->writeln("  <border>" . str_repeat($this->border, 60) . "</>");

        if (! empty($block['summary'])) {
            $this->output->writeln("  <title>{$this->icons['summary']} Summary:</>");
            $this->writeIndentedLines($block['summary']);
        }

        if (! empty($block['major_issues'])) {
            $this->output->writeln("  <major>{$this->icons['major_issues']} Major Issues:</>");
            $this->writeIndentedLines($block['major_issues']);
        }

        if (! empty($block['minor_issues'])) {
            $this->output->writeln("  <minor>{$this->icons['minor_issues']}  Minor Issues:</>");
            $this->writeIndentedLines($block['minor_issues']);
        }

        if (! empty($block['suggestions'])) {
            $this->output->writeln("  <suggestion>{$this->icons['suggestions']} Suggestions:</>");
            $this->writeIndentedLines($block['suggestions']);
        }

        $this->output->writeln("  <border>" . str_repeat($this->border, 60) . "</>");
    }

    /**
     * Write indented lines to the output.
     *
     * @param string|array $lines The lines to write, can be a single string or an array of strings.
     */
    private function writeIndentedLines(string|array $lines): void
    {
        foreach ((array) $lines as $line) {
            $this->printIndented($line);
        }
    }

    /**
     * Print a line of text with indentation.
     *
     * @param string $text The text to print.
     */
    private function printIndented(string $text): void
    {
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $this->output->writeln("     <content>" . trim($line) . "</>");
        }
    }

    /**
     * Print a section header with a title and icon.
     *
     * @param string $type The type of section (e.g., 'summary', 'files').
     * @param string $title The title of the section.
     */
    private function printSectionHeader(string $type, string $title): void
    {
        $this->output->writeln('');
        $this->output->writeln("  <title>{$this->icons[$type]} {$title}:</>");
        $this->output->writeln("  <border>" . str_repeat($this->border, 60) . "</>");
    }

    /**
     * Print the footer for a section.
     *
     * @param bool $isLast Whether this is the last section to be printed.
     */
    private function printSectionFooter(bool $isLast = false): void
    {
        $this->output->writeln("  <border>" . str_repeat($this->border, 60) . "</>");
        if (! $isLast) {
            $this->output->writeln('');
        }
    }
}
