<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\EventSubscriber;

use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\DataSourceEvent\PreBindParameters;
use FSi\Component\DataSource\Event\DataSourceEventSubscriberInterface;
use FSi\Component\DataSource\Exception\DataSourceException;
use RuntimeException;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

use function array_filter;
use function count;
use function file_exists;
use function is_dir;
use function is_string;
use function rtrim;
use function sprintf;

final class ConfigurationBuilder implements DataSourceEventSubscriberInterface
{
    private KernelInterface $kernel;
    private string $bundleConfigPath;
    private ?string $mainConfigDirectory;

    public static function getPriority(): int
    {
        return 1024;
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

    public function __invoke(PreBindParameters $event): void
    {
        $dataSource = $event->getDataSource();
        $mainConfiguration = $this->getMainConfiguration($dataSource->getName());
        if (null !== $mainConfiguration) {
            $this->buildConfiguration($dataSource, $mainConfiguration);
        } else {
            $this->buildConfigurationFromRegisteredBundles($dataSource);
        }
    }

    /**
     * @param string $dataSourceName
     * @return array{fields: array<string, array{type?: string, options?: array<string, mixed>}>} $configuration
     */
    private function getMainConfiguration(string $dataSourceName): ?array
    {
        if (null === $this->mainConfigDirectory) {
            return null;
        }

        if (false === is_dir($this->mainConfigDirectory)) {
            throw new RuntimeException("\"{$this->mainConfigDirectory}\" is not a directory!");
        }

        $configurationFile = sprintf(
            '%s/%s.yml',
            rtrim($this->mainConfigDirectory, '/'),
            $dataSourceName
        );

        if (false === file_exists($configurationFile)) {
            return null;
        }

        return $this->parseYamlFile($configurationFile);
    }

    private function buildConfigurationFromRegisteredBundles(DataSourceInterface $dataSource): void
    {
        $dataSourceName = $dataSource->getName();
        $bundles = $this->kernel->getBundles();
        $eligibleBundles = array_filter(
            $bundles,
            fn(BundleInterface $bundle): bool
                => true === file_exists($this->createBundlePathForFile($bundle, $dataSourceName, 'yml'))
                    || true === file_exists($this->createBundlePathForFile($bundle, $dataSourceName, 'yaml'))
        );

        // The idea here is that the last found configuration should be used
        $configuration = $this->findLastBundleConfiguration($dataSourceName, $eligibleBundles);
        if (0 !== count($configuration)) {
            $this->buildConfiguration($dataSource, $configuration);
        }
    }

    /**
     * @param string $dataSourceName
     * @param array<BundleInterface> $eligibleBundles
     * @return array{fields: array<string, array{type?: string, options?: array<string, mixed>}>} $configuration
     */
    private function findLastBundleConfiguration(string $dataSourceName, array $eligibleBundles): array
    {
        /** @var array{fields: array<string, array{type?: string, options?: array<string, mixed>}>} $configuration */
        $configuration = array_reduce(
            $eligibleBundles,
            function (array $configuration, BundleInterface $bundle) use ($dataSourceName): array {
                $overridingConfiguration = $this->getOverridingConfiguration($bundle, $dataSourceName);
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
     * @param string $dataSourceName
     * @return array{fields: array<string, array{type?: string, options?: array<string, mixed>}>} $configuration|null
     */
    private function getOverridingConfiguration(BundleInterface $bundle, string $dataSourceName): ?array
    {
        $ymlFile = $this->createBundlePathForFile($bundle, $dataSourceName, 'yml');
        $yamlFile = $this->createBundlePathForFile($bundle, $dataSourceName, 'yaml');
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
     * @param DataSourceInterface $dataSource
     * @param array{fields: array<string, array{type?: string, options?: array<string, mixed>}>} $configuration
     */
    private function buildConfiguration(DataSourceInterface $dataSource, array $configuration): void
    {
        foreach ($configuration['fields'] as $name => $field) {
            $type = $field['type'] ?? null;
            if (null === $type) {
                throw new DataSourceException("No type for field \"{$name}\".");
            }

            $dataSource->addField($name, $type, $field['options'] ?? []);
        }
    }

    /**
     * @param string $path
     * @return array{fields: array<string, array{type?: string, options?: array<string, mixed>}>} $configuration
     */
    private function parseYamlFile(string $path): array
    {
        $yamlContents = file_get_contents($path);
        if (false === is_string($yamlContents)) {
            throw new RuntimeException("Unable to read contents of the file '{$path}'");
        }

        return Yaml::parse($yamlContents);
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
