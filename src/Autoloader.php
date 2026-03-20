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
namespace Predis;

/**
 * Implements a lightweight PSR-0 compliant autoloader for Predis.
 *
 * @author Eric Naeseth <eric@thumbtack.com>
 * @author Daniele Alessandri <suppakilla@gmail.com>
 * @codeCoverageIgnore
 */
class Autoloader
{
    private $directory;
    private $prefix;
    private $prefix_length;
    /**
     * @param string $baseDirectory Base directory where the source files are located.
     */
    public function __construct($base_directory = __DIR__)
    {
        $this->directory = $base_directory;
        $this->prefix = __NAMESPACE__ . '\\';
        $this->prefix_length = strlen($this->prefix);
    }
    /**
     * Registers the autoloader class with the PHP SPL autoloader.
     *
     * @param bool $prepend Prepend the autoloader on the stack instead of appending it.
     */
    public static function register($prepend = false): void
    {
        spl_autoload_register([new self(), 'autoload'], true, $prepend);
    }
    /**
     * Loads a class from a file using its fully qualified name.
     *
     * @param string $className Fully qualified name of a class.
     */
    public function autoload($class_name): void
    {
        if (0 === strpos($class_name, $this->prefix)) {
            $parts = explode('\\', substr($class_name, $this->prefix_length));
            $filepath = $this->directory . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';
            if (is_file($filepath)) {
                require $filepath;
            }
        }
    }
}