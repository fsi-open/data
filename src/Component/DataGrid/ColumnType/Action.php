<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\ColumnType;

use FSi\Component\DataGrid\Column\ColumnAbstractType;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Column\ColumnTypeExtensionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_key_exists;

class Action extends ColumnAbstractType
{
    protected OptionsResolver $actionOptionsResolver;

    /**
     * @param array<ColumnTypeExtensionInterface> $columnTypeExtensions
     */
    public function __construct(array $columnTypeExtensions = [])
    {
        parent::__construct($columnTypeExtensions);
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
            'redirect_uri' => null,
            'domain' => null,
            'protocol' => 'http://'
        ]);

        $this->actionOptionsResolver->setRequired([
            'uri_scheme'
        ]);

        $this->actionOptionsResolver->setAllowedTypes('redirect_uri', ['string', 'null']);
        $this->actionOptionsResolver->setAllowedTypes('uri_scheme', 'string');
        $this->actionOptionsResolver->setAllowedValues('protocol', ['http://', 'https://']);
    }

    protected function filterValue(ColumnInterface $column, $value)
    {
        $return = [];
        $actions = $column->getOption('actions');

        foreach ($actions as $name => $options) {
            $options = $this->actionOptionsResolver->resolve((array) $options);
            $return[$name] = [];

            $url = (isset($options['protocol'], $options['domain'])) ? $options['protocol'] . $options['domain'] : '';
            $url .= vsprintf($options['uri_scheme'], $value);

            if (true === array_key_exists('redirect_uri', $options) && is_string($options['redirect_uri'])) {
                if (strpos($url, '?') !== false) {
                    $url .= '&redirect_uri=' . urlencode($options['redirect_uri']);
                } else {
                    $url .= '?redirect_uri=' . urlencode($options['redirect_uri']);
                }
            }

            $return[$name]['url'] = $url;
            $return[$name]['field_mapping_values'] = $value;
        }

        return $return;
    }
}
