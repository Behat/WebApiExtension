<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use PHPUnit\Framework\Assert;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Behat context class.
 */
class FeatureContext implements Context
{
    /**
     * @var string
     */
    private $phpBin;

    /**
     * @var Process
     */
    private $process;

    /**
     * @var string
     */
    private $workingDir;

    /**
     * Cleans test folders in the temporary directory.
     *
     * @BeforeSuite
     * @AfterSuite
     */
    public static function cleanTestFolders()
    {
        if (is_dir($dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'behat-web-api')) {
            self::clearDirectory($dir);
        }
    }

    /**
     * Prepares test folders in the temporary directory.
     *
     * @BeforeScenario
     */
    public function prepareScenario()
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'behat-web-api' . DIRECTORY_SEPARATOR. (string) (microtime(true) * rand(0, 10000));

        mkdir($dir . '/features/bootstrap', 0777, true);

        $phpFinder = new PhpExecutableFinder();
        if (false === $php = $phpFinder->find()) {
            throw new \RuntimeException('Unable to find the PHP executable.');
        }
        $this->workingDir = $dir;
        $this->phpBin = $php;
        $this->process = new Process(null);
    }

    /**
     * Creates a file with specified name and context in current working dir.
     *
     * @Given /^(?:there is )?a file named "([^"]*)" with:$/
     *
     * @param string       $filename name of the file (relative path)
     * @param PyStringNode $content  PyString string instance
     */
    public function aFileNamedWith($filename, PyStringNode $content)
    {
        $content = strtr((string) $content, ['\'\'\'' => '"""']);
        $this->createFile($this->workingDir . '/' . $filename, $content);
    }

    /**
     * Runs behat command with provided parameters
     *
     * @When /^I run "behat(?: ((?:\"|[^"])*))?"$/
     *
     * @param string $argumentsString
     */
    public function iRunBehat($argumentsString = '')
    {
        $argumentsString = strtr($argumentsString, ['\'' => '"']);

        $this->process->setWorkingDirectory($this->workingDir);
        $this->process->setCommandLine(
            sprintf(
                '%s %s %s %s',
                $this->phpBin,
                escapeshellarg(BEHAT_BIN_PATH),
                $argumentsString,
                strtr('--format-settings=\'{"timer": false}\' --no-colors', array('\'' => '"', '"' => '\"'))
            )
        );
        $this->process->start();
        $this->process->wait();
    }

    /**
     * Checks whether previously runned command passes|failes with provided output.
     *
     * @Then /^it should (fail|pass) with:$/
     *
     * @param string       $success "fail" or "pass"
     * @param PyStringNode $text    PyString text instance
     */
    public function itShouldPassWith($success, PyStringNode $text)
    {
        $this->itShouldFail($success);
        $this->theOutputShouldContain($text);
    }

    /**
     * Checks whether last command output contains provided string.
     *
     * @Then the output should contain:
     *
     * @param PyStringNode $text PyString text instance
     */
    public function theOutputShouldContain(PyStringNode $text)
    {
        Assert::assertContains($this->getExpectedOutput($text), $this->getOutput());
    }

    /**
     * @param PyStringNode $expectedText
     *
     * @return null|string|string[]
     */
    private function getExpectedOutput(PyStringNode $expectedText)
    {
        $text = strtr($expectedText, ['\'\'\'' => '"""']);

        // windows path fix
        if ('/' !== DIRECTORY_SEPARATOR) {
            $text = preg_replace_callback(
                '/ features\/[^\n ]+/', function ($matches) {
                    return str_replace('/', DIRECTORY_SEPARATOR, $matches[0]);
                }, $text
            );
            $text = preg_replace_callback(
                '/\<span class\="path"\>features\/[^\<]+/', function ($matches) {
                    return str_replace('/', DIRECTORY_SEPARATOR, $matches[0]);
                }, $text
            );
            $text = preg_replace_callback(
                '/\+[fd] [^ ]+/', function ($matches) {
                    return str_replace('/', DIRECTORY_SEPARATOR, $matches[0]);
                }, $text
            );
        }

        return $text;
    }

    /**
     * Checks whether previously run command failed|passed.
     *
     * @Then /^it should (fail|pass)$/
     *
     * @param string $success "fail" or "pass"
     */
    public function itShouldFail($success)
    {
        if ('fail' === $success) {
            if (0 === $this->getExitCode()) {
                echo 'Actual output:' . PHP_EOL . PHP_EOL . $this->getOutput();
            }

            Assert::assertNotEquals(0, $this->getExitCode());
        } else {
            if (0 !== $this->getExitCode()) {
                echo 'Actual output:' . PHP_EOL . PHP_EOL . $this->getOutput();
            }

            Assert::assertEquals(0, $this->getExitCode());
        }
    }

    /**
     * @return int|null
     */
    private function getExitCode()
    {
        return $this->process->getExitCode();
    }

    /**
     * @return string
     */
    private function getOutput()
    {
        $output = $this->process->getErrorOutput() . $this->process->getOutput();

        // Normalize the line endings in the output
        if ("\n" !== PHP_EOL) {
            $output = str_replace(PHP_EOL, "\n", $output);
        }

        return trim(preg_replace("/ +$/m", '', $output));
    }

    /**
     * @param string $filename
     * @param string $content
     */
    private function createFile($filename, $content)
    {
        $path = dirname($filename);
        if (is_dir($path) === false) {
            mkdir($path, 0777, true);
        }

        file_put_contents($filename, $content);
    }

    /**
     * @param string $path
     */
    private static function clearDirectory($path)
    {
        $files = scandir($path);
        array_shift($files);
        array_shift($files);

        foreach ($files as $file) {
            $file = $path . DIRECTORY_SEPARATOR . $file;
            is_dir($file) === true ? self::clearDirectory($file) : unlink($file);
        }

        rmdir($path);
    }
}
