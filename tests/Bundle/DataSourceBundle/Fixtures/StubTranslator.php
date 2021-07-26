<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataSourceBundle\Fixtures;

use Symfony\Contracts\Translation\TranslatorInterface;

class StubTranslator implements TranslatorInterface
{
    private string $locale = 'en';

    public function trans($id, array $parameters = array(), $domain = null, $locale = null): string
    {
        return '[trans]' . $id . '[/trans]';
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
