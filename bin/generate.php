#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * OData vocabulary generator — package-developer tool.
 *
 * Fetches CSDL XML from the vocabulary URIs declared in VocabularyCatalog::default()
 * and emits typed PHP annotation classes under src/Vocabularies/.
 *
 * Usage:
 *   php bin/generate.php               # generate all vocabularies
 *   php bin/generate.php --vocabulary=UI   # generate one vocabulary by alias
 *
 * symfony/console is a transitive dependency (via illuminate/console) so no
 * extra composer require is needed.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use LaravelUi5\OData\Vocabularies\Generator\VocabularyGenerator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

$app = new Application('odata-vocab-gen', '1.0.0');

$app->register('generate')
    ->setDescription('Generate typed OData vocabulary annotation classes from CSDL XML sources')
    ->addOption(
        'vocabulary',
        null,
        InputOption::VALUE_OPTIONAL,
        'Restrict generation to a single vocabulary by alias (e.g. UI, Core)',
    )
    ->setCode(function (InputInterface $input, OutputInterface $output): int {
        $alias = $input->getOption('vocabulary');

        $generator = new VocabularyGenerator(
            outputRoot: dirname(__DIR__) . '/src',
            output:     fn(string $line) => $output->writeln($line),
        );

        $ok = $generator->run($alias !== null && $alias !== '' ? $alias : null);

        return $ok ? Command::SUCCESS : Command::FAILURE;
    });

$app->setDefaultCommand('generate', true);
$app->run();
