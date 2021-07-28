<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\Extension\Configuration\EventSubscriber;

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

class ConfigurationBuilder implements DataGridEventSubscriberInterface
{
    private const BUNDLE_CONFIG_PATH = '%s/Resources/config/datagrid/%s.yml';
    private const MAIN_CONFIG_DIRECTORY = 'datagrid.yaml.main_config';

    private KernelInterface $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public static function getPriority(): int
    {
        return 128;
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
     * @param array<string,mixed> $configuration
     */
    private function buildConfiguration(DataGridInterface $dataGrid, array $configuration): void
    {
        foreach ($configuration['columns'] as $name => $column) {
            $dataGrid->addColumn($name, $column['type'] ?? 'text', $column['options'] ?? []);
        }
    }

    /**
     * @param string $dataGridName
     * @return array<string,mixed>|null
     */
    private function getMainConfiguration(string $dataGridName): ?array
    {
        $directory = $this->kernel->getContainer()->getParameter(self::MAIN_CONFIG_DIRECTORY);
        if (null === $directory) {
            return null;
        }
        if (false === is_string($directory)) {
            throw new RuntimeException(
                sprintf('"%s" parameter must be a string but is %s', self::MAIN_CONFIG_DIRECTORY, gettype($directory))
            );
        }

        if (false === is_dir($directory)) {
            throw new RuntimeException("\"{$directory}\" is not a directory!");
        }

        $configurationFile = sprintf('%s/%s.yml', rtrim($directory, '/'), $dataGridName);
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
            static function (BundleInterface $bundle) use ($dataGridName): bool {
                return file_exists(sprintf(self::BUNDLE_CONFIG_PATH, $bundle->getPath(), $dataGridName));
            }
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
     * @return array<string,mixed>
     */
    private function findLastBundleConfiguration(string $dataGridName, array $eligibleBundles): array
    {
        return array_reduce(
            $eligibleBundles,
            function (array $configuration, BundleInterface $bundle) use ($dataGridName): array {
                $overridingConfiguration = $this->parseYamlFile(
                    sprintf(self::BUNDLE_CONFIG_PATH, $bundle->getPath(), $dataGridName)
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
     * @param string $path
     * @return array<string,mixed>
     */
    private function parseYamlFile(string $path): array
    {
        $contents = file_get_contents($path);
        if (false === is_string($contents)) {
            throw new RuntimeException("Unable to read contentes of file {$path}");
        }

        return Yaml::parse($contents);
    }
}
