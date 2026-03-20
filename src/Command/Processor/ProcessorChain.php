<?php

declare (strict_types=1);
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
use Predis\Command\Command_Interface;
use Return_Type_Will_Change;
use Traversable;
/**
 * Default implementation of a command processors chain.
 */
class Processor_Chain implements ArrayAccess, Processor_Interface
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
    public function add(Processor_Interface $processor): void
    {
        $this->processors[] = $processor;
    }
    /**
     * {@inheritdoc}
     */
    public function remove(Processor_Interface $processor): void
    {
        if (false !== $index = array_search($processor, $this->processors, true)) {
            unset($this[$index]);
        }
    }
    /**
     * {@inheritdoc}
     */
    public function process(Command_Interface $command): void
    {
        for ($i = 0; $i < $count = count($this->processors); ++$i) {
            $this->processors[$i]->process($command);
        }
    }
    /**
     * {@inheritdoc}
     */
    public function get_processors()
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
    #[Return_Type_Will_Change]
    public function offsetExists($index)
    {
        return isset($this->processors[$index]);
    }
    /**
     * @param  int                $index
     * @return ProcessorInterface
     */
    #[Return_Type_Will_Change]
    public function offsetGet($index)
    {
        return $this->processors[$index];
    }
    /**
     * @param  int                $index
     * @param  ProcessorInterface $processor
     */
    #[Return_Type_Will_Change]
    public function offsetSet($index, $processor): void
    {
        if (!$processor instanceof Processor_Interface) {
            throw new InvalidArgumentException('Processor chain accepts only instances of `Predis\Command\Processor\ProcessorInterface`');
        }
        $this->processors[$index] = $processor;
    }
    /**
     * @param  int  $index
     */
    #[Return_Type_Will_Change]
    public function offsetUnset($index): void
    {
        unset($this->processors[$index]);
        $this->processors = array_values($this->processors);
    }
}