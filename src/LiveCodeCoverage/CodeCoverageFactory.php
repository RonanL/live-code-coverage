<?php

namespace LiveCodeCoverage;

use PHPUnit\TextUI\XmlConfiguration\Configuration;
use PHPUnit\TextUI\XmlConfiguration\Loader;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;

final class CodeCoverageFactory
{
    /**
     * @param string $phpunitFilePath
     * @return CodeCoverage
     */
    public static function createFromPhpUnitConfiguration($phpunitFilePath)
    {
        $codeCoverage = self::createDefault();

        $loader = new Loader();
        self::configure($codeCoverage, $loader->load($phpunitFilePath));

        return $codeCoverage;
    }

    private static function configure(CodeCoverage $codeCoverage, Configuration $configuration)
    {
        $codeCoverageConfiguration = $configuration->codeCoverage();

        // The following code is copied from PHPUnit\TextUI\TestRunner
        if ($codeCoverageConfiguration->hasNonEmptyListOfFilesToBeIncludedInCodeCoverageReport()) {
            if ($codeCoverageConfiguration->includeUncoveredFiles()) {
                $codeCoverage->includeUncoveredFiles();
            } else {
                $codeCoverage->excludeUncoveredFiles();
            }

            if ($codeCoverageConfiguration->processUncoveredFiles()) {
                $codeCoverage->processUncoveredFiles();
            } else {
                $codeCoverage->doNotProcessUncoveredFiles();
            }
        }

        /*
         * `FilterMapper` is not covered by PHPUnit's backward-compatibility promise, but let's use it instead of
         * copying it.
         */
        self::mapFilter($codeCoverage->filter(), $configuration->codeCoverage());
    }

    /**
     * @return CodeCoverage
     */
    public static function createDefault()
    {
        $filter = new Filter();
        $driverSelector = new Selector();
        $driver = $driverSelector->forLineCoverage($filter);
        return new CodeCoverage($driver, $filter);
    }

    public static function mapFilter(Filter $filter, \PHPUnit\TextUI\XmlConfiguration\CodeCoverage\CodeCoverage $configuration): void
    {
        foreach ($configuration->directories() as $directory) {
            $filter->includeDirectory(
                $directory->path(),
                $directory->suffix(),
                $directory->prefix(),
            );
        }

        foreach ($configuration->files() as $file) {
            $filter->includeFile($file->path());
        }

        foreach ($configuration->excludeDirectories() as $directory) {
            $filter->excludeDirectory(
                $directory->path(),
                $directory->suffix(),
                $directory->prefix(),
            );
        }

        foreach ($configuration->excludeFiles() as $file) {
            $filter->excludeFile($file->path());
        }
    }
}
