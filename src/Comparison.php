<?php
declare(strict_types=1);

namespace MichalHepner\PhpDiff;

class Comparison
{
    protected DifferenceCollection $differences;
    protected mixed $combination;

    public function __construct(
        protected mixed $a,
        protected mixed $b,
        DifferenceCollection $differences = null,
    ) {
        $this->differences = $differences ?? new DifferenceCollection();
    }

    public function isEqual(): bool
    {
        return $this->differences->isEmpty();
    }

    public function getA(): mixed
    {
        return $this->a;
    }

    public function getB(): mixed
    {
        return $this->b;
    }

    public function getDifferences(): DifferenceCollection
    {
        return $this->differences;
    }

    public function addDifference(Difference $difference): void
    {
        $this->differences->add($difference);
    }

    public function getCombination(): mixed
    {
        return $this->combination;
    }

    public function setCombination(mixed $combination): void
    {
        $this->combination = $combination;
    }
}
