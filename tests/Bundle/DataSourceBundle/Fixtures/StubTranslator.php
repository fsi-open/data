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

final class StubTranslator implements TranslatorInterface
{
    private string $locale = 'en';

    /**
     * @param string $id
     * @param array<string,string> $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null): string
    {
        return "[trans]{$id}[/trans]";
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
