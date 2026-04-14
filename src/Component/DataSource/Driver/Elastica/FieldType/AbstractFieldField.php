<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Elastica\FieldType;

use Elastica\Query\Exists;
use FSi\Component\DataSource\Driver\Elastica\Exception\ElasticaDriverException;
use Elastica\Query\BoolQuery;
use Elastica\Query\Range;
use Elastica\Query\Term;
use Elastica\Query\Terms;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\Type\AbstractFieldType as CoreAbstractFieldType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_values;
use function is_array;

abstract class AbstractFieldField extends CoreAbstractFieldType implements FieldTypeInterface
{
    public function buildQuery(BoolQuery $query, BoolQuery $filter, FieldInterface $field): void
    {
        $data = $field->getParameter();
        if (true === $this->isEmpty($data)) {
            return;
        }

        $fieldPath = $field->getOption('field');

        switch ($field->getOption('comparison')) {
            case 'eq':
                $termFilter = new Term();
                $termFilter->setTerm($fieldPath, $data);

                $filter->addMust($termFilter);
                break;
            case 'neq':
                $termFilter = new Term();
                $termFilter->setTerm($fieldPath, $data);

                $filter->addMustNot($termFilter);
                break;
            case 'between':
                if (false === is_array($data)) {
                    throw new ElasticaDriverException("'between' comparison needs an array");
                }
                if (null !== ($data['from'] ?? null)) {
                    $filter->addMust(new Range($fieldPath, ['gte' => $data['from']]));
                }
                if (null !== ($data['to'] ?? null)) {
                    $filter->addMust(new Range($fieldPath, ['lte' => $data['to']]));
                }
                break;
            case 'lt':
            case 'lte':
            case 'gt':
            case 'gte':
                $filter->addMust(new Range($fieldPath, [$field->getOption('comparison') => $data]));
                break;
            case 'in':
                if (false === is_array($data)) {
                    throw new ElasticaDriverException("'in' comparison needs an array");
                }
                $filter->addMust(new Terms($fieldPath, array_values($data)));
                break;
            case 'notIn':
                if (false === is_array($data)) {
                    throw new ElasticaDriverException("'notIn' comparison needs an array");
                }
                $filter->addMustNot(new Terms($fieldPath, array_values($data)));
                break;
            case 'isNull':
                $existsQuery = new Exists($fieldPath);
                if ('null' === $data) {
                    $filter->addMustNot($existsQuery);
                } elseif ('no_null' === $data) {
                    $filter->addMust($existsQuery);
                }
                break;
            default:
                throw new ElasticaDriverException(
                    sprintf('Unexpected comparison type ("%s").', $field->getOption('comparison'))
                );
        }
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        parent::initOptions($optionsResolver);

        $optionsResolver
            ->setDefault('field', null)
            ->setAllowedTypes('field', ['string', 'null'])
            ->setNormalizer('field', static fn(Options $options, $value) => $value ?? $options['name'])
            ->setAllowedValues(
                'comparison',
                ['eq', 'neq', 'between', 'lt', 'lte', 'gt', 'gte', 'in', 'notIn', 'isNull']
            )
        ;
    }
}
