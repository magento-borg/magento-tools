<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool;

class FileReader
{
    /**
     * Active stack
     *
     * @var array
     */
    protected static $stack = [];

    /**
     * Read file names from filesystem.
     *
     * @param array $dirPatterns
     * @param $fileNamePattern
     * @param bool $recursive
     * @return string[] filePath => $className
     */
    public function read(array $dirPatterns, $fileNamePattern, $recursive = true)
    {
        $result = [];
        foreach ($dirPatterns as $oneDirPattern) {
            $oneDirPattern = str_replace('\\', '/', $oneDirPattern);
            $entriesInDir = self::glob("{$oneDirPattern}/{$fileNamePattern}", self::GLOB_NOSORT | self::GLOB_BRACE);
            $subDirs = self::glob("{$oneDirPattern}/*", self::GLOB_ONLYDIR | self::GLOB_NOSORT | self::GLOB_BRACE);
            $filesInDir = array_diff($entriesInDir, $subDirs);

            if ($recursive) {
                $filesInSubDir = $this->read($subDirs, $fileNamePattern);
                $result = array_merge($result, $filesInDir, $filesInSubDir);
            }
        }
        $result = array_filter(
            $result,
            function ($item) {
                return strpos($item, '/Test/') === false;
            }
        );
        return $result;
    }

    /**#@+
     * Glob constants.
     */
    const GLOB_MARK     = 0x01;
    const GLOB_NOSORT   = 0x02;
    const GLOB_NOCHECK  = 0x04;
    const GLOB_NOESCAPE = 0x08;
    const GLOB_BRACE    = 0x10;
    const GLOB_ONLYDIR  = 0x20;
    const GLOB_ERR      = 0x40;
    /**#@-*/

    /**
     * Find pathnames matching a pattern.
     *
     * @see    http://docs.php.net/glob
     * @param  string  $pattern
     * @param  int $flags
     * @param  bool $forceFallback
     * @return array
     * @throws \RuntimeException
     */
    private static function glob($pattern, $flags = 0, $forceFallback = false)
    {
        if (!defined('GLOB_BRACE') || $forceFallback) {
            return static::fallbackGlob($pattern, $flags);
        }

        return static::systemGlob($pattern, $flags);
    }

    /**
     * Use the glob function provided by the system.
     *
     * @param  string  $pattern
     * @param  int     $flags
     * @return array
     * @throws \RuntimeException
     */
    private static function systemGlob($pattern, $flags)
    {
        if ($flags) {
            $flagMap = [
                self::GLOB_MARK     => GLOB_MARK,
                self::GLOB_NOSORT   => GLOB_NOSORT,
                self::GLOB_NOCHECK  => GLOB_NOCHECK,
                self::GLOB_NOESCAPE => GLOB_NOESCAPE,
                self::GLOB_BRACE    => defined('GLOB_BRACE') ? GLOB_BRACE : 0,
                self::GLOB_ONLYDIR  => GLOB_ONLYDIR,
                self::GLOB_ERR      => GLOB_ERR,
            ];

            $globFlags = 0;

            foreach ($flagMap as $internalFlag => $globFlag) {
                if ($flags & $internalFlag) {
                    $globFlags |= $globFlag;
                }
            }
        } else {
            $globFlags = 0;
        }

        self::start();
        $res = glob($pattern, $globFlags);
        $err = self::stop();
        if ($res === false) {
            throw new \RuntimeException("glob('{$pattern}', {$globFlags}) failed", 0, $err);
        }
        return $res;
    }

    /**
     * Expand braces manually, then use the system glob.
     *
     * @param  string  $pattern
     * @param  int     $flags
     * @return array
     * @throws \RuntimeException
     */
    private static function fallbackGlob($pattern, $flags)
    {
        if (!$flags & self::GLOB_BRACE) {
            return static::systemGlob($pattern, $flags);
        }

        $flags &= ~self::GLOB_BRACE;
        $length = strlen($pattern);
        $paths  = [];

        if ($flags & self::GLOB_NOESCAPE) {
            $begin = strpos($pattern, '{');
        } else {
            $begin = 0;

            while (true) {
                if ($begin === $length) {
                    $begin = false;
                    break;
                } elseif ($pattern[$begin] === '\\' && ($begin + 1) < $length) {
                    $begin++;
                } elseif ($pattern[$begin] === '{') {
                    break;
                }

                $begin++;
            }
        }

        if ($begin === false) {
            return static::systemGlob($pattern, $flags);
        }

        $next = static::nextBraceSub($pattern, $begin + 1, $flags);

        if ($next === null) {
            return static::systemGlob($pattern, $flags);
        }

        $rest = $next;

        while ($pattern[$rest] !== '}') {
            $rest = static::nextBraceSub($pattern, $rest + 1, $flags);

            if ($rest === null) {
                return static::systemGlob($pattern, $flags);
            }
        }

        $p = $begin + 1;

        while (true) {
            $subPattern = substr($pattern, 0, $begin)
                . substr($pattern, $p, $next - $p)
                . substr($pattern, $rest + 1);

            $result = static::fallbackGlob($subPattern, $flags | self::GLOB_BRACE);

            if ($result) {
                $paths = array_merge($paths, $result);
            }

            if ($pattern[$next] === '}') {
                break;
            }

            $p    = $next + 1;
            $next = static::nextBraceSub($pattern, $p, $flags);
        }

        return array_unique($paths);
    }

    /**
     * Find the end of the sub-pattern in a brace expression.
     *
     * @param  string  $pattern
     * @param  int $begin
     * @param  int $flags
     * @return int|null
     */
    private static function nextBraceSub($pattern, $begin, $flags)
    {
        $length  = strlen($pattern);
        $depth   = 0;
        $current = $begin;

        while ($current < $length) {
            if (!$flags & self::GLOB_NOESCAPE && $pattern[$current] === '\\') {
                if (++$current === $length) {
                    break;
                }

                $current++;
            } else {
                if (($pattern[$current] === '}' && $depth-- === 0) || ($pattern[$current] === ',' && $depth === 0)) {
                    break;
                } elseif ($pattern[$current++] === '{') {
                    $depth++;
                }
            }
        }

        return ($current < $length ? $current : null);
    }

    /**
     * Starting the error handler
     *
     * @param int $errorLevel
     */
    public static function start($errorLevel = \E_WARNING)
    {
        if (!static::$stack) {
            set_error_handler([get_called_class(), 'addError'], $errorLevel);
        }

        static::$stack[] = null;
    }

    /**
     * Stopping the error handler
     *
     * @param  bool $throw Throw the ErrorException if any
     * @return null|\Exception
     * @throws \Exception If an error has been catched and $throw is true
     */
    public static function stop($throw = false)
    {
        $errorException = null;

        if (static::$stack) {
            $errorException = array_pop(static::$stack);

            if (!static::$stack) {
                restore_error_handler();
            }

            if ($errorException && $throw) {
                throw $errorException;
            }
        }

        return $errorException;
    }

    /**
     * Add an error to the stack
     *
     * @param int    $errno
     * @param string $errstr
     * @param string $errfile
     * @param int    $errline
     * @return void
     */
    public static function addError($errno, $errstr = '', $errfile = '', $errline = 0)
    {
        $stack = & static::$stack[count(static::$stack) - 1];
        $stack = new \Exception($errstr, 0);
    }
}
