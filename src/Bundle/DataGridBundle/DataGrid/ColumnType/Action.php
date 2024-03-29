<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\ColumnType;

use Closure;
use FSi\Component\DataGrid\Column\ColumnAbstractType;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Column\ColumnTypeExtensionInterface;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use FSi\Component\DataGrid\Exception\UnexpectedTypeException;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function array_key_exists;

final class Action extends ColumnAbstractType
{
    private UrlGeneratorInterface $urlGenerator;
    private RequestStack $requestStack;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param RequestStack $requestStack
     * @param array<ColumnTypeExtensionInterface> $columnTypeExtensions
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack,
        DataMapperInterface $dataMapper,
        array $columnTypeExtensions
    ) {
        parent::__construct($columnTypeExtensions, $dataMapper);

        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    public function getId(): string
    {
        return 'action';
    }

    protected function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefault('actions', function (OptionsResolver $actionOptionsResolver): void {
            $actionOptionsResolver->setPrototype(true);
            $actionOptionsResolver->setDefaults([
                'redirect_uri' => true,
                'absolute' => UrlGeneratorInterface::ABSOLUTE_PATH,
                'url_attr' => [],
                'content' => null,
                'parameters_field_mapping' => [],
                'additional_parameters' => [],
            ]);

            $actionOptionsResolver->setAllowedTypes('url_attr', ['array', Closure::class]);
            $actionOptionsResolver->setAllowedTypes('content', ['null', 'string', Closure::class]);

            $actionOptionsResolver->setRequired([
                'route_name',
            ]);
        });
    }

    protected function filterValue(ColumnInterface $column, $value)
    {
        $return = [];
        $actions = $column->getOption('actions');

        foreach ($actions as $name => $options) {
            $return[$name] = [];
            $parameters = [];
            $urlAttributes = $options['url_attr'];
            $content = $options['content'];

            if (true === array_key_exists('parameters_field_mapping', $options)) {
                foreach ($options['parameters_field_mapping'] as $parameterName => $mappingField) {
                    if (true === $mappingField instanceof Closure) {
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
                    $currentRequest = $this->requestStack->getCurrentRequest();
                    if (null === $currentRequest) {
                        throw new RuntimeException("Unable to generate redirect_uri because of out of request scope");
                    }
                    $parameters['redirect_uri'] = $currentRequest->getRequestUri();
                }
            }

            if (true === $urlAttributes instanceof Closure) {
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

            if (true === $content instanceof Closure) {
                $content = (string) $content($value);
            }

            $return[$name]['content']  = $content ?? $name;
            $return[$name]['field_mapping_values'] = $value;
            $return[$name]['url_attr'] = $urlAttributes;
        }

        return $return;
    }
}
