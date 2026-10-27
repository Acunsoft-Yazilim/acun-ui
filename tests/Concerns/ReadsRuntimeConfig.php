<?php

namespace Acun\Ui\Tests\Concerns;

trait ReadsRuntimeConfig
{
    /**
     * The settings acun:layout.head prints for the JS runtime (its config island).
     *
     * @return array<string, mixed>
     */
    protected function runtimeConfig(string $html): array
    {
        self::assertSame(1, preg_match('~<script type="application/json" data-acun-ui-config="[A-Za-z0-9]{8}">(.*?)</script>~s', $html, $match), 'The page has one config island.');

        return json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
