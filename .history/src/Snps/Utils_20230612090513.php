<?php

/**
 * php-dna.
 *
 * Utility functions.
 *
 * @author          Devmanateam <devmanateam@outlook.com>
 * @copyright       Copyright (c) 2020-2023, Devmanateam
 * @license         MIT
 *
 * @link            http://github.com/familytree365/php-dna
 */

namespace Dna\Snps;

require_once 'vendor/autoload.php'; // Assuming you have installed Pandas for PHP
use atk4\dsql\Expression;

/**
 * The Singleton class defines the `GetInstance` method that serves as an
 * alternative to constructor and lets clients access the same instance of this
 * class over and over.
 */

// import datetime; // PHP has built-in date functions
// import gzip; // PHP has built-in gzip functions
// import io; // PHP has built-in I/O functions
// import logging; // You can use Monolog or another logging library in PHP
// from multiprocessing import Pool; // You can use parallel or pthreads for multi-processing in PHP
// import os; // PHP has built-in OS functions
// import re; // PHP has built-in RegExp functions
// import shutil; // PHP has built-in filesystem functions
// import tempfile; // PHP has built-in temporary file functions
// import zipfile; // PHP has built-in ZipArchive class available

// from atomicwrites import atomic_write; // You can use a library or implement atomic writes in PHP
// import pandas as pd; // There is no direct PHP alternative to pandas; consider using array functions or a data manipulation library
// import snps; // If this is a custom module, you can rewrite it in PHP and load it here

// logger = logging.getLogger(__name__); // Replace this with your preferred logging solution in PHP

class Parallelizer {
    private bool $_parallelize;
    private int $_processes;

    public function __construct(bool $parallelize = false, int $processes = null) {
        $this->_parallelize = $parallelize;
        $this->_processes = $processes ?? os_cpu_count();
    }

    public function __invoke(callable $f, array $tasks): array {
        if ($this->_parallelize) {
            // Implement parallel (multi-process) execution using pthreads, parallel or another multi-processing library
            // For example, using the parallel extension:
            $runtime = new \parallel\Runtime();
            $promises = [];
            foreach ($tasks as $task) {
                $promises[] = $runtime->run($f, [$task]);
            }
            return array_map(fn($promise) => $promise->value(), $promises);
        } else {
            return array_map($f, $tasks);
        }
    }
    
    function os_cpu_count(): int {
        // Use this function if you need to get the number of CPU cores in PHP
        // You might need to adjust this code based on your environment
        if (substr(php_uname('s'), 0, 7) == 'Windows') {
            return (int) shell_exec('echo %NUMBER_OF_PROCESSORS%');
        } else {
            return (int) shell_exec('nproc');
        }
    }
}


class Singleton {
    private static array $instances = [];

    public static function getInstance(): self {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }
        return self::$instances[$cls];
    }

    protected function __construct() {}
    private function __clone() {}
    private function __wakeup() {}

    function create_dir(string $path): bool {
        if (!file_exists($path)) {
            // Create directory if it doesn't exist
            if (!mkdir($path, 0777, true)) {
                return false;
            }
        }
        return true;
    }
    
    // Function to save DataFrame as CSV file
    function save_df_as_csv($df, $path, $filename, $comment = "", $prepend_info = true, $atomic = true, $kwargs = [])
    {
        $buffer = false;

        if (is_resource($filename)) {
            $buffer = true;
        }

        // Check if DataFrame is valid and contains data
        if (is_a($df, 'DataFrame') && count($df) > 0) {
            if (!$buffer && !$this->create_dir($path)) {
                return ""; // Unable to create directory
            }

            if ($buffer) {
                $destination = $filename;
            } else {
                $destination = $path . DIRECTORY_SEPARATOR . $filename;
                error_log("Saving " . basename($destination));
            }

            // Prepend information if required
            if ($prepend_info) {
                $s = "# Generated by snps v" . VERSION . ", https://pypi.org/project/snps/\n";
                $s .= "# Generated at " . gmdate("Y-m-d H:i:s") . " UTC\n";
            } else {
                $s = "";
            }

            $s .= $comment;

            // Set default value for 'na_rep' if not provided in $kwargs
            if (!array_key_exists('na_rep', $kwargs)) {
                $kwargs['na_rep'] = "--";
            }

            if ($buffer) {
                if (!is_a($destination, 'TextIOBase')) {
                    $s = utf8_encode($s);
                }
                fwrite($destination, $s);
                $df->to_csv($destination, $kwargs);
                fseek($destination, 0);
            } elseif ($atomic) {
                // Save DataFrame to temporary file first and then rename it to destination
                $tmpPath = tempnam(sys_get_temp_dir(), 'csv');
                file_put_contents($tmpPath, $s);
                $df->to_csv($tmpPath, 'a', $kwargs['na_rep']);
                rename($tmpPath, $destination);
            } else {
                // Save DataFrame directly to destination file
                file_put_contents($destination, $s);
                $df->to_csv($destination, 'a', $kwargs['na_rep']);
            }

            return $destination; // Return the saved file path
        } else {
            error_log("no data to save...");
            return ""; // No data to save in DataFrame
        }
    }

    function clean_str(string $s): string {
        // Replace all non-word characters (including non-alphanumeric and underscores) with an underscore
        $s = preg_replace('/\W|^(\d)/', '_', $s);
        return $s;
    }

    function zip_file($src, $dest, $arcname) {
        // Zip a file.
        // 
        // Parameters
        // ----------
        // $src : string
        //    path to file to zip
        // $dest : string
        //    path to output zip file
        // $arcname : string
        //     name of file in zip archive
        //
        // Returns
        // -------
        // string
        //     path to zipped file
    
        $zip = new ZipArchive();
    
        if ($zip->open($dest, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $zip->addFile($src, $arcname);
            $zip->close();
            return $dest;
        } else {
            return false;
        }
    }
    
    $src = 'path/to/source/file';
    $dest = 'path/to/output/zipfile.zip';
    $arcname = 'name_of_file_in_ziparchive';
    
    $result = zip_file($src, $dest, $arcname);
    
    if ($result !== false) {
        echo "Zipped file saved at: $result";
    } else {
        echo "Error zipping file.";
    }

    function gzipFile($src, $dest)
    {
        /**
         * Gzip a file.
         *
         * @param string $src  Path to file to gzip
         * @param string $dest Path to output gzip file
         *
         * @return string Path to gzipped file
         */
        
        $bufferSize = 4096;
        $srcFile = fopen($src, "rb");
        
        if ($srcFile === false) {
            throw new Exception("Cannot open source file");
        }
        
        try {
            $destFile = fopen($dest, "wb");
            
            if ($destFile === false) {
                throw new Exception("Cannot create destination file");
            }
            
            try {
                $gzFile = gzopen($dest, "wb");
                
                if ($gzFile === false) {
                    throw new Exception("Cannot create gzipped file");
                }
                
                try {
                    while (!feof($srcFile)) {
                        $buffer = fread($srcFile, $bufferSize);
                        gzwrite($gzFile, $buffer);
                    }
                } finally {
                    gzclose($gzFile);
                }
            } finally {
                fclose($destFile);
            }
        } finally {
            fclose($srcFile);
        }
        
        return $dest;
    }  
    
}

?>
