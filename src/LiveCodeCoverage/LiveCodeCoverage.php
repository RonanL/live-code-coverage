<?php

namespace LiveCodeCoverage;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use Webmozart\Assert\Assert;

final class LiveCodeCoverage
{
    /**
     * @var CodeCoverage
     */
    private $codeCoverage;

    /**
     * @param string $storageDirectory
     */
    private function __construct(CodeCoverage $codeCoverage, private $storageDirectory, private $coverageId)
    {
        $this->codeCoverage = $codeCoverage;
    }

    /**
     * @param bool $collectCodeCoverage
     * @param string $storageDirectory
     * @param string|null $phpunitConfigFilePath
     * @param string $coverageId
     * @return callable
     */
    public static function bootstrap($collectCodeCoverage, $storageDirectory, $phpunitConfigFilePath = null, $coverageId = 'live-coverage')
    {
        Assert::boolean($collectCodeCoverage);
        if (!$collectCodeCoverage) {
            return function () {
                // do nothing - code coverage is not enabled
            };
        }

        if ($phpunitConfigFilePath !== null) {
            Assert::file($phpunitConfigFilePath);
            $codeCoverage = CodeCoverageFactory::createFromPhpUnitConfiguration($phpunitConfigFilePath);
        } else {
            $codeCoverage = CodeCoverageFactory::createDefault();
        }

        $liveCodeCoverage = new self($codeCoverage, $storageDirectory, $coverageId);

        $liveCodeCoverage->start();

        return $liveCodeCoverage->stopAndSave(...);
    }

    private function start()
    {
        $this->codeCoverage->start($this->coverageId);
    }

    /**
     * Stop collecting code coverage data and save it.
     */
    public function stopAndSave()
    {
        $this->codeCoverage->stop();

        Storage::storeCodeCoverage(
            $this->codeCoverage,
            $this->storageDirectory,
            uniqid(date('YmdHis'), true)
        );
    }
}
