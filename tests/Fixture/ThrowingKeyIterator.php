<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test\Fixture;

use Iterator;
use LogicException;

/**
 * An iterator over authors whose key() must never be called, so a test can tell
 * a traversal that asks for keys from one that only reads the values.
 *
 * @implements Iterator<int, Author>
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class ThrowingKeyIterator implements Iterator
{
    /**
     * The position of the iterator in the list of authors.
     *
     * @var int
     */
    private int $position = 0;

    /**
     * Constructor.
     *
     * @param Author[] $authors The authors the iterator walks over
     */
    public function __construct(private readonly array $authors)
    {
    }

    /**
     * Returns the author at the current position.
     *
     * @return Author
     */
    public function current(): Author
    {
        return $this->authors[$this->position];
    }

    /**
     * Fails, because nothing is supposed to ask for the key.
     *
     * @return int
     *
     * @throws LogicException Always
     */
    public function key(): int
    {
        throw new LogicException('The key of this iterator must not be read.');
    }

    /**
     * Moves to the next author.
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Moves back to the first author.
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Tells whether there is an author at the current position.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->authors[$this->position]);
    }
}
