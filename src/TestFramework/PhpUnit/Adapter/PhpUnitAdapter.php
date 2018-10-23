<?php
/**
 * Copyright © 2017-2018 Maks Rafalko
 *
 * License: https://opensource.org/licenses/BSD-3-Clause New BSD License
 */

declare(strict_types=1);

namespace Infection\TestFramework\PhpUnit\Adapter;

use Infection\TestFramework\AbstractTestFrameworkAdapter;
use Infection\TestFramework\HasExtraNodeVisitors;
use Infection\TestFramework\MemoryUsageAware;
use Infection\Visitor\CodeCoverageClassIgnoreVisitor;
use Infection\Visitor\CodeCoverageMethodIgnoreVisitor;

/**
 * @internal
 */
final class PhpUnitAdapter extends AbstractTestFrameworkAdapter implements MemoryUsageAware, HasExtraNodeVisitors
{
    public const JUNIT_FILE_NAME = 'phpunit.junit.xml';

    public function testsPass(string $output): bool
    {
        if (preg_match('/failures!/i', $output)) {
            return false;
        }

        if (preg_match('/errors!/i', $output)) {
            return false;
        }

        // OK (XX tests, YY assertions)
        $isOk = preg_match('/OK\s\(/', $output);

        // "OK, but incomplete, skipped, or risky tests!"
        $isOkWithInfo = preg_match('/OK\s?,/', $output);

        // "Warnings!" - e.g. when deprecated functions are used, but tests pass
        $isWarning = preg_match('/warnings!/i', $output);

        return $isOk || $isOkWithInfo || $isWarning;
    }

    public function getMemoryUsed(string $output): float
    {
        if (preg_match('/Memory: (\d+(?:\.\d+))MB/', $output, $match)) {
            return (float) $match[1];
        }

        return -1;
    }

    public function getMutationsCollectionNodeVisitors(): array
    {
        return [
            100 => new CodeCoverageClassIgnoreVisitor(),
            15 => new CodeCoverageMethodIgnoreVisitor(),
        ];
    }

    public function getName(): string
    {
        return 'PHPUnit';
    }

    public function getInitialTestsFailRecommendations(string $commandLine): string
    {
        $recommendations = parent::getInitialTestsFailRecommendations($commandLine);

        if (version_compare($this->getVersion(), '7.2', '>=')) {
            $recommendations = sprintf(
                "%s\n\n%s",
                "Infection runs the test suite in a RANDOM order. Make sure your tests do not have hidden dependencies.\n\n" .
                'You can add these attributes to `phpunit.xml` to check it: <phpunit executionOrder="random" resolveDependencies="true" ...',
                parent::getInitialTestsFailRecommendations($commandLine)
            );
        }

        return $recommendations;
    }
}
