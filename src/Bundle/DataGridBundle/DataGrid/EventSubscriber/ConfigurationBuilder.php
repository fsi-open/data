<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\EventSubscriber;

use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\Event\DataGridEventSubscriberInterface;
use FSi\Component\DataGrid\Event\PreSetDataEvent;
use RuntimeException;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

use function array_filter;
use function array_reduce;
use function count;
use function file_exists;
use function file_get_contents;
use function is_dir;
use function is_string;
use function sprintf;

final class ConfigurationBuilder implements DataGridEventSubscriberInterface
{
    private KernelInterface $kernel;
    private string $bundleConfigPath;
    private ?string $mainConfigDirectory;

    public static function getPriority(): int
    {
        return 128;
    }

    public function __construct(
        KernelInterface $kernel,
        string $bundleConfigPath,
        ?string $mainConfigDirectory
    ) {
        $this->kernel = $kernel;
        $this->bundleConfigPath = $bundleConfigPath;
        $this->mainConfigDirectory = $mainConfigDirectory;
    }

    public function __invoke(PreSetDataEvent $event): void
    {
        $dataGrid = $event->getDataGrid();
        $mainConfiguration = $this->getMainConfiguration($dataGrid->getName());
        if (null !== $mainConfiguration) {
            $this->buildConfiguration($dataGrid, $mainConfiguration);
        } else {
            $this->buildConfigurationFromRegisteredBundles($dataGrid);
        }
    }

    /**
     * @param DataGridInterface $dataGrid
     * @param array{columns: array<string, array{type?: string, options?: array<string, mixed>}>} $configuration
     */
    private function buildConfiguration(DataGridInterface $dataGrid, array $configuration): void
    {
        foreach ($configuration['columns'] as $name => $column) {
            $dataGrid->addColumn($name, $column['type'] ?? 'text', $column['options'] ?? []);
        }
    }

    /**
     * @param string $dataGridName
     * @return array{columns: array<string, array{type?: string, options?: array<string, mixed>}>}|null
     */
    private function getMainConfiguration(string $dataGridName): ?array
    {
        if (null === $this->mainConfigDirectory) {
            return null;
        }

        if (false === is_dir($this->mainConfigDirectory)) {
            throw new RuntimeException("\"{$this->mainConfigDirectory}\" is not a directory!");
        }

        $configurationFile = sprintf('%s/%s.yml', rtrim($this->mainConfigDirectory, '/'), $dataGridName);
        if (false === file_exists($configurationFile)) {
            return null;
        }

        return $this->parseYamlFile($configurationFile);
    }

    private function buildConfigurationFromRegisteredBundles(DataGridInterface $dataGrid): void
    {
        $dataGridName = $dataGrid->getName();
        $eligibleBundles = array_filter(
            $this->kernel->getBundles(),
            fn(BundleInterface $bundle): bool
                => true === file_exists($this->createBundlePathForFile($bundle, $dataGridName, 'yml'))
                    || true === file_exists($this->createBundlePathForFile($bundle, $dataGridName, 'yaml'))
        );

        // The idea here is that the last found configuration should be used
        $configuration = $this->findLastBundleConfiguration($dataGridName, $eligibleBundles);
        if (0 !== count($configuration)) {
            $this->buildConfiguration($dataGrid, $configuration);
        }
    }

    /**
     * @param string $dataGridName
     * @param array<BundleInterface> $eligibleBundles
     * @return array{columns: array<string, array{type?: string, options?: array<string, mixed>}>}
     */
    private function findLastBundleConfiguration(string $dataGridName, array $eligibleBundles): array
    {
        /** @var array{columns: array<string, array{type?: string, options?: array<string, mixed>}>} $configuration */
        $configuration = array_reduce(
            $eligibleBundles,
            function (array $configuration, BundleInterface $bundle) use ($dataGridName): array {
                $overridingConfiguration = $this->getOverridingConfiguration($bundle, $dataGridName);
                if (true === is_array($overridingConfiguration)) {
                    $configuration = $overridingConfiguration;
                }

                return $configuration;
            },
            []
        );

        return $configuration;
    }
    /**
     * @param BundleInterface $bundle
     * @param string $dataGridName
     * @return array{columns: array<string, array{type?: string, options?: array<string, mixed>}>}|null
     */
    private function getOverridingConfiguration(BundleInterface $bundle, string $dataGridName): ?array
    {
        $ymlFile = $this->createBundlePathForFile($bundle, $dataGridName, 'yml');
        $yamlFile = $this->createBundlePathForFile($bundle, $dataGridName, 'yaml');
        if (true === file_exists($ymlFile)) {
            $file = $ymlFile;
        } elseif (true === file_exists($yamlFile)) {
            $file = $yamlFile;
        } else {
            $file = null;
        }

        if (null === $file) {
            return null;
        }

        return $this->parseYamlFile($file);
    }

    /**
     * @param string $path
     * @return array{columns: array<string, array{type?: string, options?: array<string, mixed>}>}
     */
    private function parseYamlFile(string $path): array
    {
        $contents = file_get_contents($path);
        if (false === is_string($contents)) {
            throw new RuntimeException("Unable to read contents of file {$path}");
        }

        return Yaml::parse($contents);
    }

    private function createBundlePathForFile(BundleInterface $bundle, string $dataSourceName, string $extension): string
    {
        return sprintf(
            '%s/%s/%s.%s',
            $bundle->getPath(),
            $this->bundleConfigPath,
            $dataSourceName,
            $extension
        );
    }
}
