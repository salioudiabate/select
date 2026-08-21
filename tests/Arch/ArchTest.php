<?php

declare(strict_types=1);

arch('the whole package uses strict types')
    ->expect('Salioudiabate\Select')
    ->toUseStrictTypes();

arch('no debug statements are left behind')
    ->expect('Salioudiabate\Select')
    ->not->toUse(['dd', 'dump', 'var_dump', 'ray', 'die']);

arch('src never depends on the tests it is tested by')
    ->expect('Salioudiabate\Select')
    ->not->toUse('Salioudiabate\Select\Tests');
