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
use RuntimeException;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

use function is_string;

class ConfigurationBuilder implements DataSourceEventSubscriberInterface
{
    private const BUNDLE_CONFIG_PATH = '%s/Resources/config/datasource/%s.yml';
    private const MAIN_CONFIG_DIRECTORY = 'datasource.yaml.main_config';

    private KernelInterface $kernel;

    public static function getPriority(): int
    {
        return 1024;
    }

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
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
     * @return array<string,mixed>|null
     */
    private function getMainConfiguration(string $dataSourceName): ?array
    {
        $directory = $this->kernel->getContainer()->getParameter(self::MAIN_CONFIG_DIRECTORY);
        if (false === is_string($directory)) {
            return null;
        }

        if (false === is_dir($directory)) {
            throw new RuntimeException(sprintf('"%s" is not a directory!', $directory));
        }

        $configurationFile = sprintf('%s/%s.yml', rtrim($directory, '/'), $dataSourceName);
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
            static function (BundleInterface $bundle) use ($dataSourceName): bool {
                return file_exists(sprintf(self::BUNDLE_CONFIG_PATH, $bundle->getPath(), $dataSourceName));
            }
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
     * @return array<string,mixed>
     */
    private function findLastBundleConfiguration(string $dataSourceName, array $eligibleBundles): array
    {
        return array_reduce(
            $eligibleBundles,
            function (array $configuration, BundleInterface $bundle) use ($dataSourceName): array {
                $overridingConfiguration = $this->parseYamlFile(
                    sprintf(self::BUNDLE_CONFIG_PATH, $bundle->getPath(), $dataSourceName)
                );
                if (true === is_array($overridingConfiguration)) {
                    $configuration = $overridingConfiguration;
                }

                return $configuration;
            },
            []
        );
    }

    /**
     * @param DataSourceInterface $dataSource
     * @param array<string,mixed> $configuration
     */
    private function buildConfiguration(DataSourceInterface $dataSource, array $configuration): void
    {
        foreach ($configuration['fields'] as $name => $field) {
            $dataSource->addField($name, $field['type'] ?? null, $field['options'] ?? []);
        }
    }

    /**
     * @param string $path
     * @return array<string,mixed>
     */
    private function parseYamlFile(string $path): array
    {
        $yamlContents = file_get_contents($path);
        if (false === is_string($yamlContents)) {
            throw new RuntimeException("Unable to read file '{$path}' contents");
        }

        return Yaml::parse($yamlContents);
    }
}
