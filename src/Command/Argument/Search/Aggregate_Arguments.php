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
namespace Predis\Command\Argument\Search;

class Aggregate_Arguments extends Common_Arguments
{
    /**
     * Loads document attributes from the source document.
     *
     * @param  string ...$fields Could be just '*' to load all fields
     * @return $this
     */
    public function load(string ...$fields): self
    {
        $arguments = func_get_args();
        $this->arguments[] = 'LOAD';
        if ($arguments[0] === '*') {
            $this->arguments[] = '*';
            return $this;
        }
        $this->arguments[] = count($arguments);
        $this->arguments = array_merge($this->arguments, $arguments);
        return $this;
    }
    /**
     * Loads document attributes from the source document.
     *
     * @return $this
     */
    public function group_by(string ...$properties): self
    {
        $arguments = func_get_args();
        array_push($this->arguments, 'GROUPBY', count($arguments));
        $this->arguments = array_merge($this->arguments, $arguments);
        return $this;
    }
    /**
     * Groups the results in the pipeline based on one or more properties.
     *
     * If you want to add alias property to your argument just add "true" value in arguments enumeration,
     * next value will be considered as alias to previous one.
     *
     * Example: 'argument', true, 'name' => 'argument' AS 'name'
     *
     * @param  string|bool ...$argument
     * @return $this
     */
    public function reduce(string $function, ...$argument): self
    {
        $arguments = func_get_args();
        $function_value = array_shift($arguments);
        $arguments_counter = 0;
        for ($i = 0, $i_max = count($arguments); $i < $i_max; $i++) {
            if (true === $arguments[$i]) {
                $arguments[$i] = 'AS';
                $i++;
                continue;
            }
            $arguments_counter++;
        }
        array_push($this->arguments, 'REDUCE', $function_value);
        $this->arguments = array_merge($this->arguments, [$arguments_counter], $arguments);
        return $this;
    }
    /**
     * Sorts the pipeline up until the point of SORTBY, using a list of properties.
     *
     * @param  string ...$properties Enumeration of properties, including sorting direction (ASC, DESC)
     * @return $this
     */
    public function sort_by(int $max = 0, ...$properties): self
    {
        $arguments = func_get_args();
        $max_value = array_shift($arguments);
        $this->arguments[] = 'SORTBY';
        $this->arguments = array_merge($this->arguments, [count($arguments)], $arguments);
        if ($max_value !== 0) {
            array_push($this->arguments, 'MAX', $max_value);
        }
        return $this;
    }
    /**
     * Applies a 1-to-1 transformation on one or more properties and either stores the result
     * as a new property down the pipeline or replaces any property using this transformation.
     *
     * @return $this
     */
    public function apply(string $expression, string $as = ''): self
    {
        array_push($this->arguments, 'APPLY', $expression);
        if ($as !== '') {
            array_push($this->arguments, 'AS', $as);
        }
        return $this;
    }
    /**
     * Scan part of the results with a quicker alternative than LIMIT.
     *
     * @return $this
     */
    public function with_cursor(int $read_size = 0, int $idle_time = 0): self
    {
        $this->arguments[] = 'WITHCURSOR';
        if ($read_size !== 0) {
            array_push($this->arguments, 'COUNT', $read_size);
        }
        if ($idle_time !== 0) {
            array_push($this->arguments, 'MAXIDLE', $idle_time);
        }
        return $this;
    }
}