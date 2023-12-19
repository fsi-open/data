<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Elastica\FieldType;

use Elastica\Query\BoolQuery;
use Elastica\Query\Exists;
use Elastica\Query\Terms;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\Type\EntityTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

use function in_array;
use function sprintf;

class Entity extends AbstractFieldField implements EntityTypeInterface
{
    public function buildQuery(BoolQuery $query, BoolQuery $filter, FieldInterface $field): void
    {
        $data = $field->getParameter();
        if ($this->isEmpty($data)) {
            return;
        }

        $fieldPath = $field->getOption('field');
        $comparison = $field->getOption('comparison');
        if (true === in_array($comparison, ['eq', 'in'], true)) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $idFieldName = $field->getOption('identifier_field');

            if ('eq' === $comparison) {
                $data = [$data];
            }

            $ids = [];
            foreach ($data as $entity) {
                $ids[] = $accessor->getValue($entity, $idFieldName);
            }

            $filter->addMust(new Terms(sprintf("%s.%s", $fieldPath, $idFieldName), $ids));
        } elseif ('isNull' === $comparison) {
            $existsQuery = new Exists($fieldPath);
            if ('null' === $data) {
                $filter->addMustNot($existsQuery);
            } elseif ('no_null' === $data) {
                $filter->addMust($existsQuery);
            }
        }
    }

    public function getId(): string
    {
        return 'entity';
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        parent::initOptions($optionsResolver);

        $optionsResolver
            ->setAllowedValues('comparison', ['eq', 'in', 'isNull'])
            ->setDefaults(['identifier_field' => 'id'])
            ->setAllowedTypes('identifier_field', ['string'])
        ;
    }
}
