<?php

use Pug\Pug;

class PugIssuesTest extends PHPUnit_Framework_TestCase
{
    public function testIssue62()
    {
        $pug = new Pug();
        $html = trim($pug->render('.MyInitialClass(class=$classes)', array(
            'classes' => 'MyClass',
        )));
        $expected = '<div class="MyInitialClass MyClass"></div>';

        $this->assertSame($expected, $html);
    }

    public function testIssue64()
    {
        $pug = new Pug();
        $html = trim($pug->render("script.\n" . '  var url = "/path/#{$foo->bar}/file";', array(
            'foo' => (object) array(
                'bar' => 'hello/world',
            ),
        )));
        $expected = '<script>var url = "/path/hello/world/file";</script>';

        $this->assertSame($expected, $html);
    }

    public function testIssue71()
    {
        $pug = new Pug(array(
            'singleQuote' => false,
            'expressionLanguage' => 'js',
        ));
        $actual = trim($pug->render('input(type="checkbox", name="group[" + group.id + "]")', array(
            'group' => (object) array(
                'id' => 4,
            ),
        )));

        $this->assertSame('<input type="checkbox" name="group[4]" />', $actual);
    }

    public function testIssue73()
    {
        $pug = new Pug();
        $actual = trim($pug->render('p=__("foo")'));

        $this->assertSame('<p>foo</p>', $actual);
    }

    public function testIssue75()
    {
        $pug = new Pug(array(
            'cache' => false,
        ));
        $requirements = $pug->requirements();

        $this->assertTrue($requirements['cacheFolderExists']);
        $this->assertTrue($requirements['cacheFolderIsWritable']);
    }

    public function testIssue86()
    {
        $pug = new Pug(array(
            'expressionLanguage' => 'js',
        ));
        $actual = trim($pug->render("a(href='?m=' + i.a)=i.b", array(
            'i' => array(
                'a' => 1,
                'b' => 2,
            ),
        )));
        $expected = '<a href="?m=1">2</a>';

        $this->assertSame($expected, $actual);
    }

    public function testissue89()
    {
        include_once __DIR__ . '/../lib/FooBarClass.php';

        $pug = new Pug(array(
            'expressionLanguage' => 'auto',
        ));
        $actual = trim($pug->render("if errors.has('email')\n  = errors.first('email')", array(
            'errors' => new \FooBarClass(),
        )));

        $this->assertSame('foo', $actual);

        $pug = new Pug(array(
            'expressionLanguage' => 'js',
        ));
        $actual = trim($pug->render("if errors.has('email')\n  = errors.first('email')", array(
            'errors' => new \FooBarClass(),
        )));

        $this->assertSame('foo', $actual);
    }

    public function testIssue90()
    {
        $pug = new Pug(array(
            'expressionLanguage' => 'js',
        ));
        $actual = str_replace("\n", '', trim($pug->render('p= \'$test\'
p= "$test"
p= \'#{$test}\'
p= "#{$test}"
p #{$test}

p(
    data-a=\'$test\'
    data-b="$test"
    data-c=\'#{$test}\'
    data-d="#{$test}"
) test', array(
            'test' => 'foo',
        ))));
        $expected = '<p>$test</p><p>$test</p><p>#{$test}</p><p>#{$test}</p><p>foo</p><p data-a="$test" data-b="$test" data-c="#{$test}" data-d="#{$test}">test</p>';

        $this->assertSame($expected, $actual);

        $pug = new Pug(array(
            'expressionLanguage' => 'php',
        ));
        $actual = str_replace("\n", '', trim($pug->render('p= \'$test\'
p= "$test"
p= \'#{$test}\'
p= "#{$test}"
p #{$test}

p(
    data-a=\'$test\'
    data-b="$test"
    data-c=\'#{$test}\'
    data-d="#{$test}"
    data-e="#${test}"
) test', array(
            'test' => 'foo',
        ))));
        $expected = '<p>$test</p><p>foo</p><p>#{$test}</p><p>#foo</p><p>foo</p><p data-a="$test" data-b="foo" data-c="#{$test}" data-d="#foo" data-e="#foo">test</p>';

        $this->assertSame($expected, $actual);
    }

    public function testIssue92()
    {
        $pug = new Pug(['debug' => false]);
        $actual = trim($pug->render('
mixin simple-paragraph(str)
    p=str
+simple-paragraph(strtoupper("foo"))
'));
        $expected = '<p>FOO</p>';

        $this->assertSame($expected, $actual);

        $actual = trim($pug->render('
mixin simple-paragraph(str)
    p=str
+simple-paragraph(strtoupper(substr("foo
---\'(bar
", 0, 3)) + \'\\""\' + (substr(\'")5\', 1)))
+simple-paragraph(strtoupper(\'b\') + "foo")
'));
        $expected = '<p>FOO\\&quot;&quot;)5</p><p>Bfoo</p>';

        $this->assertSame($expected, $actual);
    }

    public function testIssue72()
    {
        $pug = new Pug(array(
            'expressionLanguage' => 'js',
        ));
        $actual = trim($pug->render('
if entryopen && !submitted
    button
', array(
    'entryopen' => true,
    'submitted' => false,
)));
        $expected = '<button></button>';

        $this->assertSame($expected, $actual);

        $pug = new Pug(array(
            'expressionLanguage' => 'php',
        ));
        $actual = trim($pug->render('
if $entryopen and !$submitted
    button
', array(
    'entryopen' => true,
    'submitted' => false,
)));
        $expected = '<button></button>';

        $this->assertSame($expected, $actual);
    }

    public function testSymfonyIssue6()
    {
        /**
         * With js expression language.
         */
        $pug = new Pug(array(
            'expressionLanguage' => 'js',
        ));
        $actual = trim($pug->render('
.foo(style=\'background-position: 50% -402px; background-image: url("\' + strtolower(\'/img.PNG\') + \'");\')
'));
        $expected = '<div class="foo" style="background-position: 50% -402px; background-image: url(&quot;/img.png&quot;);"></div>';

        // style as string
        $this->assertSame($expected, $actual);

        $actual = trim($pug->render('
.foo(style={\'background-position\': "50% -402px", \'background-image\': \'url("\' + strtolower(\'/img.PNG\') + \'")\'})
'));
        $expected = '<div class="foo" style="background-position:50% -402px;background-image:url(&quot;/img.png&quot;)"></div>';

        // style as object
        $this->assertSame($expected, $actual);

        /**
         * With php expression language.
         */
        $pug = new Pug(array(
            'expressionLanguage' => 'php',
        ));
        $actual = trim($pug->render('
.foo(style=(\'background-position: 50% -402px; background-image: url("\' . strtolower(\'/img.PNG\') . \'");\'))
'));
        $expected = '<div class="foo" style="background-position: 50% -402px; background-image: url(&quot;/img.png&quot;);"></div>';

        // style as string
        $this->assertSame($expected, $actual);

        $actual = trim($pug->render('
.foo(style=array(\'background-position\' => "50% -402px", \'background-image\' => \'url("\' . strtolower(\'/img.PNG\') . \'")\'))
'));
        $expected = '<div class="foo" style="background-position:50% -402px;background-image:url(&quot;/img.png&quot;)"></div>';

        // style as array
        $this->assertSame($expected, $actual);
    }

    public function testIssue100()
    {
        $pug = new Pug(array(
            'expressionLanguage' => 'js',
        ));
        $actual = str_replace("\n", '', trim($pug->render('p Example #{item.name} #{helpers.format(\'money\', item.price)}', array(
            'item' => array(
                'name' => 'Foo',
                'price' => 12,
            ),
            'helpers' => array(
                'format' => function ($type, $price) {
                    return $type . '-' . $price;
                },
            ),
        ))));
        $expected = '<p>Example Foo money-12</p>';

        $this->assertSame($expected, $actual);
    }

    public function testIssue103()
    {
        $pug = new Pug(array(
            'expressionLanguage' => 'js',
        ));
        $actual = trim($pug->render("mixin a\n  p\n+a"));
        $expected = '<p></p>';

        $this->assertSame($expected, $actual);
    }

    /**
     * https://github.com/BKWLD/laravel-pug/issues/28
     */
    public function testMethodCallsInStatements()
    {
        foreach (array('js', 'auto') as $expressionLanguage) {
            $pug = new Pug(array(
                'expressionLanguage' => $expressionLanguage,
            ));
            $actual = trim($pug->render(implode("\n", array(
                'if zone.getLocation()',
                '  each val in zone.getLocation()',
                '    p=val',
            )), array(
                'zone' => new DateTimeZone("Europe/Prague"),
            )));
            $expected = '`<p>CZ</p><p>[\d.]+</p><p>[\d.]+</p><p></p>`';

            $this->assertRegExp($expected, $actual);
        }
    }

    public function testIssue154()
    {
        $pug = new Pug();
        $actual = str_replace("\n", '', trim($pug->render('p #[em #[strong Yow!]]')));
        $expected = '<p><em><strong>Yow!</strong></em></p>';

        $this->assertSame($expected, $actual);
    }

    public function testCoffeeScriptFilterRegression()
    {
        $input = implode("\n", array(
            'body',
            '  :verbatim',
            '    # Assignment:',
            '    number   = 42',
            '    opposite = true',
            '',
            '    # Conditions:',
            '    number = -42 if opposite',
            '',
            '    # Functions:',
            '    square = (x) -> x * x',
            '',
            '    # Arrays:',
            '    list = [1, 2, 3, 4, 5]',
            '',
            '    # Objects:',
            '    math =',
            '      root:   Math.sqrt',
            '      square: square',
            '      cube:   (x) -> x * square x',
        ));
        $expected = implode("\n", array(
            '<body>',
            '# Assignment:',
            'number   = 42',
            'opposite = true',
            '',
            '# Conditions:',
            'number = -42 if opposite',
            '',
            '# Functions:',
            'square = (x) -> x * x',
            '',
            '# Arrays:',
            'list = [1, 2, 3, 4, 5]',
            '',
            '# Objects:',
            'math =',
            '  root:   Math.sqrt',
            '  square: square',
            '  cube:   (x) -> x * square x</body>',
        ));
        $pug = new Pug([
            'filters' => [
                'verbatim' => function ($string) {
                    return $string;
                },
            ],
        ]);

        $actual = $pug->render($input);
        $this->assertSame($expected, $actual);
    }
}
