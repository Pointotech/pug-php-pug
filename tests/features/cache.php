<?php

use Pug\Pug;

class PugTest extends Pug
{
    protected $compilationsCount = 0;

    public function getCompilationsCount()
    {
        return $this->compilationsCount;
    }

    public function compile($input, $filename = null)
    {
        $this->compilationsCount++;

        return parent::compile($input, $filename);
    }

    public function compileFile($input)
    {
        $this->compilationsCount++;

        return parent::compileFile($input);
    }
}

class PugCacheTest extends PHPUnit_Framework_TestCase
{
    protected function emptyDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $file) {
            if ($file !== '.' && $file !== '..') {
                $path = $dir . '/' . $file;
                if (is_dir($path)) {
                    $this->emptyDirectory($path);
                } else {
                    unlink($path);
                }
            }
        }
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage ///cannot/be/created: Cache directory doesn't exist
     */
    public function testMissingDirectory()
    {
        $pug = new Pug(array(
            'singleQuote' => false,
            'cache' => '///cannot/be/created'
        ));
        $pug->render(__DIR__ . '/../templates/attrs.pug');
    }

    /**
     * Cache from string input
     */
    public function testStringInputCache()
    {
        $dir = sys_get_temp_dir() . '/pug';
        if (file_exists($dir)) {
            if (is_file($dir)) {
                unlink($dir);
                mkdir($dir);
            } else {
                $this->emptyDirectory($dir);
            }
        } else {
            mkdir($dir);
        }
        $pug = new PugTest(array(
            'debug' => false,
            'cache' => $dir
        ));
        $this->assertSame(0, $pug->getCompilationsCount(), 'Should have done no compilations yet');
        $pug->render("header\n  h1#foo Hello World!\nfooter");
        $this->assertSame(1, $pug->getCompilationsCount(), 'Should have done 1 compilation');
        $pug->render("header\n  h1#foo Hello World!\nfooter");
        $this->assertSame(1, $pug->getCompilationsCount(), 'Should have done always 1 compilation because the code is cached');
        $pug->render("header\n  h1#foo Hello World2\nfooter");
        $this->assertSame(2, $pug->getCompilationsCount(), 'Should have done always 2 compilations because the code changed');
        $this->emptyDirectory($dir);
    }

    /**
     * Cache from string input
     */
    public function testFileCache()
    {
        $dir = sys_get_temp_dir() . '/pug';
        if (file_exists($dir)) {
            if (is_file($dir)) {
                unlink($dir);
                mkdir($dir);
            } else {
                $this->emptyDirectory($dir);
            }
        } else {
            mkdir($dir);
        }
        $test = "$dir/test".mt_rand(0,9999999).'.pug';
        file_put_contents($test, "header\n  h1#foo Hello World!\nfooter");
        sleep(1);
        $pug = new PugTest(array(
            'debug' => false,
            'cache' => $dir
        ));
        $this->assertSame(0, $pug->getCompilationsCount(), 'Should have done no compilations yet');
        $pug->renderFile($test);
        $this->assertSame(1, $pug->getCompilationsCount(), 'Should have done 1 compilation');
        $pug->renderFile($test);
        $this->assertSame(1, $pug->getCompilationsCount(), 'Should have done always 1 compilation because the code is cached');
        file_put_contents($test, "header\n  h1#foo Hello World2\nfooter");
        $pug->renderFile($test);
        $this->assertSame(2, $pug->getCompilationsCount(), 'Should have done always 2 compilations because the code changed');
        $this->emptyDirectory($dir);
        unlink($test);
    }

    private function cacheSystem($keepBaseName)
    {
        $cacheDirectory = sys_get_temp_dir() . '/pug-test';
        $this->emptyDirectory($cacheDirectory);
        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0777, true);
        }
        $base = 'Pug';
        $file = tempnam(sys_get_temp_dir(), $base);
        $pug = new Pug(array(
            'singleQuote' => false,
            'keepBaseName' => $keepBaseName,
            'cache' => $cacheDirectory,
        ));
        copy(__DIR__ . '/../templates/attrs.pug', $file);
        $pug->renderFile($file);
        $phpFiles = array_values(array_map(function ($file) use ($cacheDirectory) {
            return $cacheDirectory . DIRECTORY_SEPARATOR . $file;
        }, array_filter(scandir($cacheDirectory), function ($file) {
            return substr($file, -4) === '.php';
        })));
        $this->assertSame(1, count($phpFiles), 'The cached file should now exist.');
        $cachedFile = realpath($phpFiles[0]);
        $this->assertFalse(!$cachedFile, 'The cached file should now exist.');
        $containsBase = strpos($cachedFile, $base) !== false;
        $this->assertTrue($containsBase === $keepBaseName, 'The cached file name should contains base name if keepBaseName is true.');
        unlink($cachedFile);
    }

    /**
     * Normal function
     */
    public function testCache()
    {
        $this->cacheSystem(false);
    }

    /**
     * Test option keepBaseName
     */
    public function testCacheWithKeepBaseName()
    {
        $this->cacheSystem(true);
    }

    /**
     * Test cacheDirectory method
     */
    public function testCacheDirectory()
    {
        $cacheDirectory = sys_get_temp_dir() . '/pug-test';
        $this->emptyDirectory($cacheDirectory);
        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0777, true);
        }
        $templatesDirectory = __DIR__ . '/../templates';
        $pug = new Pug(array(
            'basedir' => $templatesDirectory,
            'cache' => $cacheDirectory,
        ));
        list($success, $errors) = $pug->cacheDirectory($templatesDirectory);
        $filesCount = count(array_filter(scandir($cacheDirectory), function ($file) {
            return $file !== '.' && $file !== '..';
        }));
        $expectedCount = count(array_filter(array_merge(
            scandir($templatesDirectory),
            scandir($templatesDirectory . '/auxiliary'),
            scandir($templatesDirectory . '/auxiliary/subdirectory/subsubdirectory')
        ), function ($file) {
            return in_array(pathinfo($file, PATHINFO_EXTENSION), array('pug', 'Pug'));
        }));
        $this->emptyDirectory($cacheDirectory);
        $templatesDirectory = __DIR__ . '/../templates/subdirectory/subsubdirectory';
        $pug = new Pug(array(
            'basedir' => $templatesDirectory,
            'cache' => $cacheDirectory,
        ));
        $this->emptyDirectory($cacheDirectory);
        rmdir($cacheDirectory);

        $this->assertSame($expectedCount, $success + $errors, 'Each .pug file in the directory to cache should generate a success or an error.');
        $this->assertSame($success, $filesCount, 'Each file successfully cached should be in the cache directory.');
    }
}
