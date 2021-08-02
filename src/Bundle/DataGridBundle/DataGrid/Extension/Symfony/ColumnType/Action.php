<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\ColumnType;

use FSi\Component\DataGrid\Column\ColumnAbstractType;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Column\ColumnTypeExtensionInterface;
use FSi\Component\DataGrid\Exception\UnexpectedTypeException;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function array_key_exists;
use function is_callable;

class Action extends ColumnAbstractType
{
    protected UrlGeneratorInterface $urlGenerator;
    protected RequestStack $requestStack;
    protected OptionsResolver $actionOptionsResolver;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param RequestStack $requestStack
     * @param array<ColumnTypeExtensionInterface> $columnTypeExtensions
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack,
        array $columnTypeExtensions = []
    ) {
        parent::__construct($columnTypeExtensions);

        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
        $this->actionOptionsResolver = new OptionsResolver();
    }

    public function getId(): string
    {
        return 'action';
    }

    public function getActionOptionsResolver(): OptionsResolver
    {
        return $this->actionOptionsResolver;
    }

    protected function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'actions' => [],
        ]);

        $optionsResolver->setAllowedTypes('actions', 'array');

        $this->actionOptionsResolver->setDefaults([
            'redirect_uri' => true,
            'absolute' => UrlGeneratorInterface::ABSOLUTE_PATH,
            'url_attr' => [],
            'content' => null,
            'parameters_field_mapping' => [],
            'additional_parameters' => [],
        ]);

        $this->actionOptionsResolver->setAllowedTypes('url_attr', ['array', 'Closure']);
        $this->actionOptionsResolver->setAllowedTypes('content', ['null', 'string', 'Closure']);

        $this->actionOptionsResolver->setRequired([
            'route_name',
        ]);
    }

    protected function filterValue(ColumnInterface $column, $value)
    {
        $return = [];
        $actions = $column->getOption('actions');

        foreach ($actions as $name => $options) {
            $options = $this->actionOptionsResolver->resolve((array) $options);
            $return[$name] = [];
            $parameters = [];
            $urlAttributes = $options['url_attr'];
            $content = $options['content'];

            if (true === array_key_exists('parameters_field_mapping', $options)) {
                foreach ($options['parameters_field_mapping'] as $parameterName => $mappingField) {
                    if (true === is_callable($mappingField)) {
                        $parameters[$parameterName] = $mappingField($value);
                    } else {
                        $parameters[$parameterName] = $value[$mappingField];
                    }
                }
            }

            if (true === array_key_exists('additional_parameters', $options)) {
                foreach ($options['additional_parameters'] as $parameterValueName => $parameterValue) {
                    $parameters[$parameterValueName] = $parameterValue;
                }
            }

            if (false !== $options['redirect_uri']) {
                if (true === is_string($options['redirect_uri'])) {
                    $parameters['redirect_uri'] = $options['redirect_uri'];
                }

                if (true === $options['redirect_uri']) {
                    $masterRequest = $this->requestStack->getMasterRequest();
                    if (null === $masterRequest) {
                        throw new RuntimeException("Unable to generate redirect_uri because of out of request scope");
                    }
                    $parameters['redirect_uri'] = $masterRequest->getRequestUri();
                }
            }

            if (true === is_callable($urlAttributes)) {
                $urlAttributes = $urlAttributes($value);

                if (false === is_array($urlAttributes)) {
                    throw new UnexpectedTypeException(
                        'url_attr option Closure must return new array with url attributes.'
                    );
                }
            }

            $url = $this->urlGenerator->generate($options['route_name'], $parameters, $options['absolute']);

            if (false === array_key_exists('href', $urlAttributes)) {
                $urlAttributes['href'] = $url;
            }

            if (null !== $content && true === is_callable($content)) {
                $content = (string) $content($value);
            }

            $return[$name]['content']  = $content ?? $name;
            $return[$name]['field_mapping_values'] = $value;
            $return[$name]['url_attr'] = $urlAttributes;
        }

        return $return;
    }
}
