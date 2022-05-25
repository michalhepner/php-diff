<?php
declare(strict_types=1);

namespace MichalHepner\PhpDiff;

use InvalidArgumentException;

class Comparator
{
    protected const SUPPORTED_TYPES = ['boolean', 'integer', 'string', 'array', 'NULL', 'object'];

    protected bool $ignoreArraySorting = false;

    public function __construct(protected mixed $a, protected $b)
    {}

    public function calculate(): Comparison
    {
        $a = $this->a;
        $b = $this->b;

        if ($this->ignoreArraySorting) {
            $this->recursivelySort($a);
            $this->recursivelySort($b);
        }

        return $this->doCalculate($a, $b, '$', new Comparison($a, $b));
    }

    protected function recursivelySort(mixed &$a): void
    {
        if (is_array($a)) {
            // First we need to check if there are any child arrays to sort them first
            $hasChildArray = false;
            foreach ($a as $child) {
                if (is_array($child)) {
                    $hasChildArray = true;
                }
            }

            if ($hasChildArray) {
                foreach ($a as &$child) {
                    if (is_array($child)) {
                        $this->recursivelySort($child);
                    }
                }
            }

            // Need to check if we have a regular PHP array with only integer keys going from 0 to count - 1
            $allKeys = array_keys($a);
            if (count($allKeys) > 0 && $allKeys === range(min($allKeys), max($allKeys))) {
                usort($a, function ($item1, $item2) {
                    return strcmp(
                        hash('sha256', json_encode($item1)),
                        hash('sha256', json_encode($item2))
                    );
                });
            }
        }
    }

    protected function doCalculate(mixed $a, mixed $b, string $path, Comparison $comparison): Comparison
    {
        $aType = gettype($a);
        $bType = gettype($b);

        if (!in_array($aType, self::SUPPORTED_TYPES)) {
            throw new InvalidArgumentException(sprintf('%s does not support %s data type', __CLASS__, $aType));
        }

        if (!in_array($bType, self::SUPPORTED_TYPES)) {
            throw new InvalidArgumentException(sprintf('%s does not support %s data type', __CLASS__, $aType));
        }

        if ($aType !== $bType) {
            $diff = new Difference($a, $b, $path);
            $comparison->addDifference($diff);
            $comparison->setCombination($diff);

            return $comparison;
        }

        $type = $aType;

        switch ($type) {
            case 'boolean':
            case 'integer':
            case 'string':
            case 'NULL':
                if ($a !== $b) {
                    $diff = new Difference($a, $b, $path);
                    $comparison->addDifference($diff);
                    $comparison->setCombination($diff);
                } else {
                    $comparison->setCombination($a);
                }

                return $comparison;
            case 'object':
                if (get_class($a) !== 'stdClass') {
                    throw new \InvalidArgumentException(sprintf('Only objects of stdClass can be compared. %s given', get_class($a)));
                }
                if (get_class($b) !== 'stdClass') {
                    throw new \InvalidArgumentException(sprintf('Only objects of stdClass can be compared. %s given', get_class($b)));
                }
            case 'array':
                return $this->compareArrays((array) $a, (array) $b, $path, $comparison);
        }
    }

    protected function compareArrays(array $a, array $b, string $path, Comparison $comparison): Comparison
    {
        ksort($a);
        ksort($b);

        $keys = array_unique(array_merge(array_keys($a), array_keys($b)));
        sort($keys);

        $ret = [];

        foreach ($keys as $key) {
            $keyPath = $path . '.' . $key;
            if (isset($a[$key]) && isset($b[$key])) {
                $tmpComparison = new Comparison($a[$key], $b[$key]);
                $this->doCalculate($a[$key], $b[$key], $keyPath, $tmpComparison);
                $ret[$key] = $tmpComparison->getCombination();
                foreach ($tmpComparison->getDifferences() as $tmpDifference) {
                    $comparison->addDifference($tmpDifference);
                }
            } elseif (isset($a[$key])) {
                $diff = new Difference($a[$key], null, $keyPath);
                $comparison->addDifference($diff);
                $ret[$key] = $diff;
            } elseif (isset($b[$key])) {
                $diff = new Difference(null, $b[$key], $keyPath);
                $comparison->addDifference($diff);
                $ret[$key] = $diff;
            }
        }

        $comparison->setCombination($ret);

        return $comparison;
    }

    public function getIgnoreArraySorting(): bool
    {
        return $this->ignoreArraySorting;
    }

    public function setIgnoreArraySorting(bool $ignoreArraySorting): void
    {
        $this->ignoreArraySorting = $ignoreArraySorting;
    }
}
