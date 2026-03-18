<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Processor;

use ArrayAccess;
use ArrayIterator;
use InvalidArgumentException;
use Predis\Command\CommandInterface;
use ReturnTypeWillChange;
use Traversable;

/**
 * Default implementation of a command processors chain.
 */
class ProcessorChain implements ArrayAccess, ProcessorInterface
{
    private $processors = [];

    /**
     * @param array $processors List of instances of ProcessorInterface.
     */
    public function __construct($processors = [])
    {
        foreach ($processors as $processor) {
            $this->add($processor);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(ProcessorInterface $processor): void
    {
        $this->processors[] = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(ProcessorInterface $processor): void
    {
        if (false !== $index = array_search($processor, $this->processors, true)) {
            unset($this[$index]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(CommandInterface $command): void
    {
        for ($i = 0; $i < $count = count($this->processors); ++$i) {
            $this->processors[$i]->process($command);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessors()
    {
        return $this->processors;
    }

    /**
     * Returns an iterator over the list of command processor in the chain.
     *
     * @return Traversable<int, ProcessorInterface>
     */
    public function getIterator(): \ArrayIterator
    {
        return new ArrayIterator($this->processors);
    }

    /**
     * Returns the number of command processors in the chain.
     */
    public function count(): int
    {
        return count($this->processors);
    }

    /**
     * @param  int  $index
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function offsetExists($index)
    {
        return isset($this->processors[$index]);
    }

    /**
     * @param  int                $index
     * @return ProcessorInterface
     */
    #[ReturnTypeWillChange]
    public function offsetGet($index)
    {
        return $this->processors[$index];
    }

    /**
     * @param  int                $index
     * @param  ProcessorInterface $processor
     */
    #[ReturnTypeWillChange]
    public function offsetSet($index, $processor): void
    {
        if (!$processor instanceof ProcessorInterface) {
            throw new InvalidArgumentException(
                'Processor chain accepts only instances of `Predis\Command\Processor\ProcessorInterface`'
            );
        }

        $this->processors[$index] = $processor;
    }

    /**
     * @param  int  $index
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($index): void
    {
        unset($this->processors[$index]);
        $this->processors = array_values($this->processors);
    }
}
