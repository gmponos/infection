<?php
/**
 * Copyright © 2017-2018 Maks Rafalko
 *
 * License: https://opensource.org/licenses/BSD-3-Clause New BSD License
 */

declare(strict_types=1);

namespace Infection\Tests\Mutator\Cast;

use Infection\Tests\Mutator\AbstractMutatorTestCase;

/**
 * @internal
 */
final class CastObjectTest extends AbstractMutatorTestCase
{
    /**
     * @dataProvider provideMutationCases
     */
    public function test_mutator($input, $expected = null): void
    {
        $this->doTest($input, $expected);
    }

    public function provideMutationCases(): \Generator
    {
        yield 'It removes casting to object' => [
            <<<'PHP'
<?php

(object) ['test' => 1];
PHP
            ,
            <<<'PHP'
<?php

['test' => 1];
PHP
            ,
        ];
    }
}
