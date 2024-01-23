<?php

namespace LiveCodeCoverage;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Webmozart\Assert\Assert;

final class RemoteCodeCoverage
{
    public const string COVERAGE_ID_KEY = 'coverage_id';
    public const string COLLECT_CODE_COVERAGE_KEY = 'collect_code_coverage';
    public const string COVERAGE_GROUP_KEY = 'coverage_group';
    public const string EXPORT_CODE_COVERAGE_KEY = 'export_code_coverage';

    /**
     * Enable remote code coverage.
     *
     * @param bool $coverageEnabled Whether or not code coverage should be enabled
     * @param string $storageDirectory Where to store the generated coverage data files
     * @param string $phpunitConfigFilePath The path to the PHPUnit XML file containing the coverage filter configuration
     * @return callable Call this value at the end of the request life cycle.
     */
    public static function bootstrap($coverageEnabled, $storageDirectory, $phpunitConfigFilePath = null)
    {
        Assert::boolean($coverageEnabled);
        if (!$coverageEnabled) {
            return function () {
                // do nothing - code coverage is not enabled
            };
        }
        $cache = new FilesystemAdapter();

        $coverageGroup = $cache->get('coverage_group', function (ItemInterface $item): ?string {
            $item->expiresAfter(0);

            return $_GET[self::COVERAGE_GROUP_KEY] ?? $_COOKIE[self::COVERAGE_GROUP_KEY] ?? null;
        });

        $storageDirectory .= ($coverageGroup ? '/' . $coverageGroup : '');

        if (isset($_GET[self::EXPORT_CODE_COVERAGE_KEY])) {
            header('Content-Type: text/plain');
            echo self::exportCoverageData($storageDirectory);
            exit;
        }

        $coverageId = $cache->get('coverage_id', function (ItemInterface $item): string {
            $item->expiresAfter(0);

            return $_GET[self::COVERAGE_ID_KEY] ?? $_COOKIE[self::COVERAGE_ID_KEY] ?? 'live-coverage';
        });

        $collectCodeCoverage = $cache->get('collect_code_coverage', function (ItemInterface $item): string {
            $item->expiresAfter(0);

            return isset($_COOKIE[self::COLLECT_CODE_COVERAGE_KEY]) && (bool)$_COOKIE[self::COLLECT_CODE_COVERAGE_KEY];
        }) === 'true';

        return LiveCodeCoverage::bootstrap(
            $collectCodeCoverage,
            $storageDirectory,
            $phpunitConfigFilePath,
            $coverageId
        );
    }

    /**
     * Get previously collected coverage data (combines all coverage data stored in the given directory, merges and serializes it).
     *
     * @param string $coverageDirectory
     * @return string
     */
    public static function exportCoverageData($coverageDirectory)
    {
        $codeCoverage = Storage::loadFromDirectory($coverageDirectory);

        return serialize($codeCoverage);
    }
}
