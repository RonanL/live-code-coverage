<?php

namespace LiveCodeCoverage;

use PHPUnit\TextUI\Configuration\SourceMapper;
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
        // The following code is copied from PHPUnit\TextUI\TestRunner
        if ($configuration->source()->notEmpty()) {
            if ($configuration->codeCoverage()->includeUncoveredFiles()) {
                $codeCoverage->includeUncoveredFiles();
            } else {
                $codeCoverage->excludeUncoveredFiles();
            }
        }

        self::mapFilter($codeCoverage->filter(), $configuration);
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

    public static function mapFilter(Filter $filter, Configuration $configuration): void
    {
        foreach ($configuration->source()->includeDirectories() as $directory) {
            $filter->includeFiles(array_keys((new SourceMapper)->map($configuration->source())));
        }

        foreach ($configuration->source()->includeFiles() as $file) {
            $filter->includeFile($file->path());
        }

        foreach ($configuration->source()->excludeDirectories() as $directory) {
            foreach (array_keys((new SourceMapper)->map($configuration->source())) as $file) {
                $filter->excludeFile($file);
            }
        }

        foreach ($configuration->source()->excludeFiles() as $file) {
            $filter->excludeFile($file->path());
        }
    }
}
