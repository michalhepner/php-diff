<?php
declare(strict_types=1);

namespace MichalHepner\PhpDiff;

use MichalHepner\Collection\ObjectCollection;

class DifferenceCollection extends ObjectCollection
{
    public function toArray(): array
    {
        $ret = [];
        foreach ($this as $difference) {
            $ret[] = $difference->toArray();
        }

        return $ret;
    }
}
