<?php

namespace Mim\Component\Dotenv\Tests;

use Mim\Component\Dotenv\Exceptions\FormatException;
use Mim\Component\Dotenv\Exceptions\PathException;
use PHPUnit\Framework\TestCase;
use Mim\Component\Dotenv\Dotenv;
use const DIRECTORY_SEPARATOR;

class DotenvTest extends TestCase
{
    /**
     * @dataProvider getEnvDataWithFormatErrors
     */
    public function testParseWithFormatError($data, $error)
    {
        $dotenv = new Dotenv();

        try {
            $dotenv->parse($data);
            $this->fail('Should throw a FormatException');
        } catch (FormatException $e) {
            $this->assertStringMatchesFormat($error, $e->getMessage());
        }
    }

    public function getEnvDataWithFormatErrors(): array
    {
        $tests = [
            ['FOO=BAR BAZ', "A value containing spaces must be surrounded by quotes in \".env\" at line 1.\n...FOO=BAR BAZ...\n             ^ line 1 offset 11"],
            ['FOO BAR=BAR', "Whitespace characters are not supported after the variable name in \".env\" at line 1.\n...FOO BAR=BAR...\n     ^ line 1 offset 3"],
            ['FOO', "Missing = in the environment variable declaration in \".env\" at line 1.\n...FOO...\n     ^ line 1 offset 3"],
            ['FOO="foo', "Missing quote to end the value in \".env\" at line 1.\n...FOO=\"foo...\n          ^ line 1 offset 8"],
            ['FOO=\'foo', "Missing quote to end the value in \".env\" at line 1.\n...FOO='foo...\n          ^ line 1 offset 8"],
            ["FOO=\"foo\nBAR=\"bar\"", "Missing quote to end the value in \".env\" at line 1.\n...FOO=\"foo\\nBAR=\"bar\"...\n                     ^ line 1 offset 18"],
            ['FOO=\'foo'."\n", "Missing quote to end the value in \".env\" at line 1.\n...FOO='foo\\n...\n            ^ line 1 offset 9"],
            ['export FOO', "Unable to unset an environment variable in \".env\" at line 1.\n...export FOO...\n            ^ line 1 offset 10"],
            ['FOO=${FOO', "Unclosed braces on variable expansion in \".env\" at line 1.\n...FOO=\${FOO...\n           ^ line 1 offset 9"],
            ['FOO= BAR', "Whitespace are not supported before the value in \".env\" at line 1.\n...FOO= BAR...\n      ^ line 1 offset 4"],
            ['Стасян', "Invalid character in variable name in \".env\" at line 1.\n...Стасян...\n  ^ line 1 offset 0"],
            ['FOO!', "Missing = in the environment variable declaration in \".env\" at line 1.\n...FOO!...\n     ^ line 1 offset 3"],
            ['FOO=$(echo foo', "Missing closing parenthesis. in \".env\" at line 1.\n...FOO=$(echo foo...\n                ^ line 1 offset 14"],
            ['FOO=$(echo foo'."\n", "Missing closing parenthesis. in \".env\" at line 1.\n...FOO=$(echo foo\\n...\n                ^ line 1 offset 14"],
            ["FOO=\nBAR=\${FOO:-\'a{a}a}", "Unsupported character \"'\" found in the default value of variable \"\$FOO\". in \".env\" at line 2.\n...\\nBAR=\${FOO:-\'a{a}a}...\n                       ^ line 2 offset 24"],
            ["FOO=\nBAR=\${FOO:-a\$a}", "Unsupported character \"\$\" found in the default value of variable \"\$FOO\". in \".env\" at line 2.\n...FOO=\\nBAR=\${FOO:-a\$a}...\n                       ^ line 2 offset 20"],
            ["FOO=\nBAR=\${FOO:-a\"a}", "Unclosed braces on variable expansion in \".env\" at line 2.\n...FOO=\\nBAR=\${FOO:-a\"a}...\n                    ^ line 2 offset 17"],
        ];

        return $tests;
    }

    public function testLoad()
    {
        unset($_ENV['FOO']);
        unset($_ENV['BAR']);
        unset($_SERVER['FOO']);
        unset($_SERVER['BAR']);
        putenv('FOO');
        putenv('BAR');

        @mkdir($tmpdir = sys_get_temp_dir().'/dotenv');

        $path1 = tempnam($tmpdir, 'sf-');
        $path2 = tempnam($tmpdir, 'sf-');

        file_put_contents($path1, 'FOO=BAR');
        file_put_contents($path2, 'BAR=BAZ');

        (new Dotenv())->usePutenv()->load($path1, $path2);

        $foo = getenv('FOO');
        $bar = getenv('BAR');

        putenv('FOO');
        putenv('BAR');
        unlink($path1);
        unlink($path2);
        rmdir($tmpdir);

        $this->assertSame('BAR', $foo);
        $this->assertSame('BAZ', $bar);
    }

    public function testLoadEnv()
    {
        unset($_ENV['FOO']);
        unset($_ENV['BAR']);
        unset($_SERVER['FOO']);
        unset($_SERVER['BAR']);
        putenv('FOO');
        putenv('BAR');

        @mkdir($tmpdir = sys_get_temp_dir().'/dotenv');

        $path = tempnam($tmpdir, 'sf-');

        // .env

        file_put_contents($path, 'FOO=BAR');
        (new Dotenv())->usePutenv()->loadEnv($path, 'TEST_APP_ENV');
        $this->assertSame('BAR', getenv('FOO'));
        $this->assertSame('dev', getenv('TEST_APP_ENV'));

        // .env.local

        $_SERVER['TEST_APP_ENV'] = 'local';
        file_put_contents("$path.local", 'FOO=localBAR');
        (new Dotenv())->usePutenv()->loadEnv($path, 'TEST_APP_ENV');
        $this->assertSame('localBAR', getenv('FOO'));

        // special case for test

        $_SERVER['TEST_APP_ENV'] = 'test';
        (new Dotenv())->usePutenv()->loadEnv($path, 'TEST_APP_ENV');
        $this->assertSame('BAR', getenv('FOO'));

        // .env.dev

        unset($_SERVER['TEST_APP_ENV']);
        file_put_contents("$path.dev", 'FOO=devBAR');
        (new Dotenv())->usePutenv()->loadEnv($path, 'TEST_APP_ENV');
        $this->assertSame('devBAR', getenv('FOO'));

        // .env.dev.local

        file_put_contents("$path.dev.local", 'FOO=devlocalBAR');
        (new Dotenv())->usePutenv()->loadEnv($path, 'TEST_APP_ENV');
        $this->assertSame('devlocalBAR', getenv('FOO'));

        // .env.dist

        unlink($path);
        file_put_contents("$path.dist", 'BAR=distBAR');
        (new Dotenv())->usePutenv()->loadEnv($path, 'TEST_APP_ENV');
        $this->assertSame('distBAR', getenv('BAR'));

        putenv('FOO');
        putenv('BAR');
        unlink("$path.dist");
        unlink("$path.local");
        unlink("$path.dev");
        unlink("$path.dev.local");
        rmdir($tmpdir);
    }

    public function testOverload()
    {
        unset($_ENV['FOO']);
        unset($_ENV['BAR']);
        unset($_SERVER['FOO']);
        unset($_SERVER['BAR']);

        putenv('FOO=initial_foo_value');
        putenv('BAR=initial_bar_value');
        $_ENV['FOO'] = 'initial_foo_value';
        $_ENV['BAR'] = 'initial_bar_value';

        @mkdir($tmpdir = sys_get_temp_dir().'/dotenv');

        $path1 = tempnam($tmpdir, 'sf-');
        $path2 = tempnam($tmpdir, 'sf-');

        file_put_contents($path1, 'FOO=BAR');
        file_put_contents($path2, 'BAR=BAZ');

        (new Dotenv())->usePutenv()->overload($path1, $path2);

        $foo = getenv('FOO');
        $bar = getenv('BAR');

        putenv('FOO');
        putenv('BAR');
        unlink($path1);
        unlink($path2);
        rmdir($tmpdir);

        $this->assertSame('BAR', $foo);
        $this->assertSame('BAZ', $bar);
    }

    public function testLoadDirectory()
    {
        $this->expectException(PathException::class);
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__);
    }

    public function testServerSuperGlobalIsNotOverridden()
    {
        $originalValue = $_SERVER['argc'];

        $dotenv = new Dotenv();
        $dotenv->populate(['argc' => 'new_value']);

        $this->assertSame($originalValue, $_SERVER['argc']);
    }

    public function testEnvVarIsNotOverridden()
    {
        putenv('TEST_ENV_VAR=original_value');
        $_SERVER['TEST_ENV_VAR'] = 'original_value';

        $dotenv = (new Dotenv())->usePutenv();
        $dotenv->populate(['TEST_ENV_VAR' => 'new_value']);

        $this->assertSame('original_value', getenv('TEST_ENV_VAR'));
    }

    public function testHttpVarIsPartiallyOverridden()
    {
        $_SERVER['HTTP_TEST_ENV_VAR'] = 'http_value';

        $dotenv = (new Dotenv())->usePutenv();
        $dotenv->populate(['HTTP_TEST_ENV_VAR' => 'env_value']);

        $this->assertSame('env_value', getenv('HTTP_TEST_ENV_VAR'));
        $this->assertSame('env_value', $_ENV['HTTP_TEST_ENV_VAR']);
        $this->assertSame('http_value', $_SERVER['HTTP_TEST_ENV_VAR']);
    }

    public function testEnvVarIsOverriden()
    {
        putenv('TEST_ENV_VAR_OVERRIDEN=original_value');

        $dotenv = (new Dotenv())->usePutenv();
        $dotenv->populate(['TEST_ENV_VAR_OVERRIDEN' => 'new_value'], true);

        $this->assertSame('new_value', getenv('TEST_ENV_VAR_OVERRIDEN'));
        $this->assertSame('new_value', $_ENV['TEST_ENV_VAR_OVERRIDEN']);
        $this->assertSame('new_value', $_SERVER['TEST_ENV_VAR_OVERRIDEN']);
    }

    public function testMemorizingLoadedVarsNamesInSpecialVar()
    {
        // Special variable not exists
        unset($_ENV['MIM_DOTENV_VARS']);
        unset($_SERVER['MIM_DOTENV_VARS']);
        putenv('MIM_DOTENV_VARS');

        unset($_ENV['APP_DEBUG']);
        unset($_SERVER['APP_DEBUG']);
        putenv('APP_DEBUG');
        unset($_ENV['DATABASE_URL']);
        unset($_SERVER['DATABASE_URL']);
        putenv('DATABASE_URL');

        $dotenv = (new Dotenv())->usePutenv();
        $dotenv->populate(['APP_DEBUG' => '1', 'DATABASE_URL' => 'mysql://root@localhost/db']);

        $this->assertSame('APP_DEBUG,DATABASE_URL', getenv('MIM_DOTENV_VARS'));

        // Special variable has a value
        $_ENV['MIM_DOTENV_VARS'] = 'APP_ENV';
        $_SERVER['MIM_DOTENV_VARS'] = 'APP_ENV';
        putenv('MIM_DOTENV_VARS=APP_ENV');

        $_ENV['APP_DEBUG'] = '1';
        $_SERVER['APP_DEBUG'] = '1';
        putenv('APP_DEBUG=1');
        unset($_ENV['DATABASE_URL']);
        unset($_SERVER['DATABASE_URL']);
        putenv('DATABASE_URL');

        $dotenv = (new Dotenv())->usePutenv();
        $dotenv->populate(['APP_DEBUG' => '0', 'DATABASE_URL' => 'mysql://root@localhost/db']);
        $dotenv->populate(['DATABASE_URL' => 'sqlite:///somedb.sqlite']);

        $this->assertSame('APP_ENV,DATABASE_URL', getenv('MIM_DOTENV_VARS'));
    }

    public function testOverridingEnvVarsWithNamesMemorizedInSpecialVar()
    {
        putenv('MIM_DOTENV_VARS='.$_SERVER['MIM_DOTENV_VARS'] = 'FOO,BAR,BAZ');

        putenv('FOO=foo');
        putenv('BAR=bar');
        putenv('BAZ=baz');
        putenv('DOCUMENT_ROOT=/var/www');

        $dotenv = (new Dotenv())->usePutenv();
        $dotenv->populate(['FOO' => 'foo1', 'BAR' => 'bar1', 'BAZ' => 'baz1', 'DOCUMENT_ROOT' => '/boot']);

        $this->assertSame('foo1', getenv('FOO'));
        $this->assertSame('bar1', getenv('BAR'));
        $this->assertSame('baz1', getenv('BAZ'));
        $this->assertSame('/var/www', getenv('DOCUMENT_ROOT'));
    }

    public function testGetVariablesValueFromEnvFirst()
    {
        $_ENV['APP_ENV'] = 'prod';
        $dotenv = new Dotenv();

        $test = "APP_ENV=dev\nTEST1=foo1_\${APP_ENV}";
        $values = $dotenv->parse($test);
        $this->assertSame('foo1_prod', $values['TEST1']);
    }

    public function testGetVariablesValueFromGetenv()
    {
        putenv('Foo=Bar');

        $dotenv = new Dotenv();

        try {
            $values = $dotenv->parse('Foo=${Foo}');
            $this->assertSame('Bar', $values['Foo']);
        } finally {
            putenv('Foo');
        }
    }

    public function testNoDeprecationWarning()
    {
        $dotenv = new Dotenv();
        $this->assertInstanceOf(Dotenv::class, $dotenv);
    }

    public function testDoNotUsePutenv()
    {
        $dotenv = new Dotenv();
        $dotenv->populate(['TEST_USE_PUTENV' => 'no']);

        $this->assertSame('no', $_SERVER['TEST_USE_PUTENV']);
        $this->assertSame('no', $_ENV['TEST_USE_PUTENV']);
        $this->assertFalse(getenv('TEST_USE_PUTENV'));
    }

    public function testBootEnv()
    {
        @mkdir($tmpdir = sys_get_temp_dir().'/dotenv');
        $path = tempnam($tmpdir, 'sf-');

        file_put_contents($path, 'FOO=BAR');
        (new Dotenv('TEST_APP_ENV', 'TEST_APP_DEBUG'))->bootEnv($path);

        $this->assertSame('BAR', $_SERVER['FOO']);

        unset($_SERVER['FOO'], $_ENV['FOO']);
        unlink($path);

        file_put_contents($path.'.local.php', '<?php return ["TEST_APP_ENV" => "dev", "FOO" => "BAR"];');
        (new Dotenv('TEST_APP_ENV', 'TEST_APP_DEBUG'))->bootEnv($path);
        $this->assertSame('BAR', $_SERVER['FOO']);
        $this->assertSame('1', $_SERVER['TEST_APP_DEBUG']);

        unset($_SERVER['FOO'], $_ENV['FOO']);
        unlink($path.'.local.php');
        rmdir($tmpdir);
    }
}
