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

    /**
     * {@inheritdoc}
     */
    public function getInitialTestRunCommandLine(
        string $configPath,
        string $extraOptions,
        bool $includePhpArgs,
        array $phpExtraArgs
    ): array {
        try {
            $version = $this->getVersion();
        } catch (\InvalidArgumentException $e) {
            $version = 'uknown';
        }

        $testsOrder = $this->getTestsOrder($version);

        if ($testsOrder) {
            $extraOptions .= ' ' . $testsOrder;
        }

        return parent::getInitialTestRunCommandLine(
            $configPath,
            $extraOptions,
            $includePhpArgs,
            $phpExtraArgs
        );
    }

    private function getTestsOrder($version): string
    {
        if (version_compare($version, '7.3', '>=')) {
            return '--order=random';
        }

        if (version_compare($version, '7.2', '>=')) {
            return '--random-order';
        }

        return '';
    }
}
