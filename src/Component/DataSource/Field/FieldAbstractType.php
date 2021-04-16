<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Field;

use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\FieldEvent;
use FSi\Component\DataSource\Event\FieldEvents;
use FSi\Component\DataSource\Exception\FieldException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_key_exists;

abstract class FieldAbstractType implements FieldTypeInterface
{
    /**
     * @var array<string>
     */
    protected $comparisons = [];

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $comparison;

    /**
     * @var mixed
     */
    protected $parameter;

    /**
     * @var bool
     */
    private $dirty = true;

    /**
     * @var DataSourceInterface|null
     */
    private $datasource;

    /**
     * @var array<string, mixed>
     */
    private $options = [];

    /**
     * @var EventDispatcher|null
     */
    private $eventDispatcher;

    /**
     * @var OptionsResolver|null
     */
    private $optionsResolver;

    /**
     * @var array
     */
    private $extensions = [];

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function __clone()
    {
        $this->eventDispatcher = null;
        $this->optionsResolver = null;
    }

    public function setComparison(string $comparison): void
    {
        if (false === in_array($comparison, $this->getAvailableComparisons(), true)) {
            throw new FieldException(sprintf(
                'Comparison "%s" not allowed for this type of field ("%s").',
                $comparison,
                $this->getType()
            ));
        }

        $this->comparison = $comparison;
    }

    public function getComparison(): ?string
    {
        return $this->comparison;
    }

    /**
     * @return array<string>
     */
    public function getAvailableComparisons(): array
    {
        return $this->comparisons;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $this->getOptionsResolver()->resolve($options);
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options) && null !== $this->options[$name];
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getOption(string $name)
    {
        if (false === $this->hasOption($name)) {
            throw new FieldException(sprintf('There\'s no option named "%s"', $name));
        }

        return $this->options[$name];
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function bindParameter($parameter): void
    {
        $this->setDirty();

        // PreBindParameter event.
        $event = new FieldEvent\ParameterEventArgs($this, $parameter);
        $this->getEventDispatcher()->dispatch($event, FieldEvents::PRE_BIND_PARAMETER);
        $parameter = $event->getParameter();

        $datasourceName = null !== $this->getDataSource() ? $this->getDataSource()->getName() : null;
        if (null !== $datasourceName) {
            $parameter = $parameter[$datasourceName][DataSourceInterface::PARAMETER_FIELDS][$this->getName()] ?? null;
        } else {
            $parameter = null;
        }

        $this->parameter = $parameter;

        // PreBindParameter event.
        $event = new FieldEvent\FieldEventArgs($this);
        $this->getEventDispatcher()->dispatch($event, FieldEvents::POST_BIND_PARAMETER);
    }

    public function getParameter(array &$parameters): void
    {
        $datasourceName = null !== $this->getDataSource() ? $this->getDataSource()->getName() : null;
        if (null !== $datasourceName) {
            $parameter = [
                $datasourceName => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        $this->getName() => $this->getCleanParameter(),
                    ],
                ],
            ];
        } else {
            $parameter = [];
        }

        //PostGetParameter event.
        $event = new FieldEvent\ParameterEventArgs($this, $parameter);
        $this->getEventDispatcher()->dispatch($event, FieldEvents::POST_GET_PARAMETER);
        $parameter = $event->getParameter();

        $parameters = array_merge_recursive($parameters, $parameter);
    }

    public function getCleanParameter()
    {
        return $this->parameter;
    }

    public function addExtension(FieldExtensionInterface $extension): void
    {
        if (true === in_array($extension, $this->extensions, true)) {
            return;
        }

        $this->getEventDispatcher()->addSubscriber($extension);
        $extension->initOptions($this);
        $this->extensions[] = $extension;

        $this->options = $this->getOptionsResolver()->resolve($this->options);
    }

    public function setExtensions(array $extensions): void
    {
        foreach ($extensions as $extension) {
            if (false === $extension instanceof FieldExtensionInterface) {
                throw new FieldException(
                    sprintf('Expected instance of %s, %s given', FieldExtensionInterface::class, get_class($extension))
                );
            }

            $this->getEventDispatcher()->addSubscriber($extension);
            $extension->initOptions($this);
        }
        $this->options = $this->getOptionsResolver()->resolve($this->options);
        $this->extensions = $extensions;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function createView(): FieldViewInterface
    {
        $view = new FieldView($this);

        // PostBuildView event.
        $event = new FieldEvent\ViewEventArgs($this, $view);
        $this->getEventDispatcher()->dispatch($event, FieldEvents::POST_BUILD_VIEW);

        return $view;
    }

    public function isDirty(): bool
    {
        return $this->dirty;
    }

    public function setDirty(bool $dirty = true): void
    {
        $this->dirty = $dirty;
    }

    public function setDataSource(DataSourceInterface $datasource): void
    {
        $this->datasource = $datasource;
    }

    public function getDataSource(): ?DataSourceInterface
    {
        return $this->datasource;
    }

    public function initOptions(): void
    {
    }

    public function getOptionsResolver(): OptionsResolver
    {
        if (null === $this->optionsResolver) {
            $this->optionsResolver = new OptionsResolver();
        }

        return $this->optionsResolver;
    }

    protected function getEventDispatcher(): EventDispatcher
    {
        if (null === $this->eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }

    /**
     * @param mixed $data
     * @return bool
     */
    protected function isEmpty($data): bool
    {
        return ([] === $data) || ('' === $data) || (null === $data);
    }
}
