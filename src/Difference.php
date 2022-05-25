<?php
declare(strict_types=1);

namespace MichalHepner\PhpDiff;

use Serializable;
use Stringable;

class Difference implements Stringable, Serializable
{
    public function __construct(
        protected mixed $a,
        protected mixed $b,
        protected string $path,
    ) {}

    public function __toString(): string
    {
        return '!>' . $this->itemToString($this->a) . ' -> ' . $this->itemToString($this->b). '<!';
    }

    public function toArray(): array
    {
        return [
            'a' => $this->a,
            'b' => $this->b,
            'path' => $this->path,
        ];
    }

    protected function itemToString(mixed $item): string
    {
        return json_encode($item);
    }

    public function getA(): mixed
    {
        return $this->a;
    }

    public function getB(): mixed
    {
        return $this->b;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function serialize()
    {
        return $this->__toString();
    }

    public function unserialize($data)
    {
        if (is_string($data)) {
            throw new \InvalidArgumentException('Data must be a string');
        }

        $matches = [];
        if (preg_match('/^!>(.+){1} \-> (.+){1}<!$/', $data, $matches)) {
            $this->a = json_decode($matches[1]);
            $this->b = json_decode($matches[2]);
        }
    }

}
