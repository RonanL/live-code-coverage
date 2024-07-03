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
        }

        self::mapFilter($codeCoverage->filter(), $configuration->source());
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

    public static function mapFilter(Filter $filter, \PHPUnit\TextUI\Configuration\Source $configuration): void
    {
        foreach ($configuration->includeDirectories() as $directory) {
            $filter->includeDirectory(
                $directory->path(),
                $directory->suffix(),
                $directory->prefix(),
            );
        }

        foreach ($configuration->includeFiles() as $file) {
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
