<?php

declare(strict_types=1);

namespace LexiconSyntax\Tests;

use LexiconSyntax\Console\BufferedOutput;
use LexiconSyntax\Console\ConsoleApplicationFactory;
use PHPUnit\Framework\TestCase;

final class ConsoleApplicationTest extends TestCase
{
    private string $grammarFile;
    private string $installDirectory;
    private string $generateDirectory;
    private string $astDirectory;
    private string $projectDirectory;

    protected function setUp(): void
    {
        $this->grammarFile = tempnam(sys_get_temp_dir(), 'lxs_');
        self::assertIsString($this->grammarFile);
        $this->installDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lxs_bin_' . bin2hex(random_bytes(4));
        $this->generateDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lxs_gen_' . bin2hex(random_bytes(4));
        $this->astDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lxs_ast_' . bin2hex(random_bytes(4));
        $this->projectDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lxs_project_' . bin2hex(random_bytes(4));

        file_put_contents($this->grammarFile, <<<'GRAMMAR'
token Digit ::= '0' .. '9';
rule Number ::= Digit+;
GRAMMAR);
    }

    protected function tearDown(): void
    {
        if (is_file($this->grammarFile)) {
            unlink($this->grammarFile);
        }

        if (is_dir($this->installDirectory)) {
            foreach (glob($this->installDirectory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                unlink($file);
            }

            rmdir($this->installDirectory);
        }

        if (is_dir($this->generateDirectory)) {
            self::removeDirectory($this->generateDirectory);
        }

        if (is_dir($this->astDirectory)) {
            self::removeDirectory($this->astDirectory);
        }

        if (is_dir($this->projectDirectory)) {
            self::removeDirectory($this->projectDirectory);
        }
    }

    public function testHelpListsCommands(): void
    {
        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run(['lsyn', 'help'], $output);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('validate', $output->stdout);
        self::assertStringContainsString('print', $output->stdout);
        self::assertStringContainsString('ast', $output->stdout);
        self::assertStringContainsString('install-global', $output->stdout);
        self::assertStringContainsString('generate', $output->stdout);
        self::assertStringContainsString('generate:tokens', $output->stdout);
        self::assertStringContainsString('generate:ast', $output->stdout);
        self::assertStringContainsString('generate:parser', $output->stdout);
        self::assertStringContainsString('parse:c-like', $output->stdout);
    }

    public function testValidateCommandReportsOk(): void
    {
        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run(['lsyn', 'validate', $this->grammarFile], $output);

        self::assertSame(0, $exitCode);
        self::assertSame('OK' . PHP_EOL, $output->stdout);
        self::assertSame('', $output->stderr);
    }

    public function testPrintCommandFormatsGrammar(): void
    {
        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run(['lsyn', 'print', $this->grammarFile], $output);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString("token Digit ::= '0' .. '9';", $output->stdout);
        self::assertStringContainsString('rule Number ::= Digit+;', $output->stdout);
    }

    public function testValidateCommandReportsDiagnostics(): void
    {
        file_put_contents($this->grammarFile, <<<'GRAMMAR'
token Number ::= Missing+;
GRAMMAR);

        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run(['lsyn', 'validate', $this->grammarFile], $output);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString("Undefined reference 'Missing'.", $output->stdout);
    }

    public function testInstallGlobalCommandCreatesLauncher(): void
    {
        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run(['lsyn', 'install-global', $this->installDirectory], $output);

        self::assertSame(0, $exitCode);
        self::assertDirectoryExists($this->installDirectory);

        $launcher = PHP_OS_FAMILY === 'Windows'
            ? $this->installDirectory . DIRECTORY_SEPARATOR . 'lsyn.bat'
            : $this->installDirectory . DIRECTORY_SEPARATOR . 'lsyn';

        self::assertFileExists($launcher);
        self::assertStringContainsString('lsyn', file_get_contents($launcher) ?: '');
        self::assertStringContainsString('Installed', $output->stdout);
    }

    public function testInitCommandCreatesStarterGrammar(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lxs_init_' . bin2hex(random_bytes(4)) . '.lxs';

        try {
            $output = new BufferedOutput();
            $exitCode = ConsoleApplicationFactory::create()->run(['lsyn', 'init', $path], $output);

            self::assertSame(0, $exitCode);
            self::assertFileExists($path);
            self::assertStringContainsString('type Associativity = enum[left, right];', file_get_contents($path) ?: '');
            self::assertStringContainsString('Created', $output->stdout);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testInitCommandCreatesCLikeExampleProject(): void
    {
        mkdir($this->projectDirectory, 0777, true);
        $previousDirectory = getcwd();
        self::assertIsString($previousDirectory);
        chdir($this->projectDirectory);

        try {
            $output = new BufferedOutput();
            $exitCode = ConsoleApplicationFactory::create()->run(['lsyn', 'init', 'c-like'], $output);

            self::assertSame(0, $exitCode);
            self::assertFileExists($this->projectDirectory . DIRECTORY_SEPARATOR . 'c-like.lxs');
            self::assertFileExists($this->projectDirectory . DIRECTORY_SEPARATOR . 'c-like.lxs.json');
            self::assertFileExists($this->projectDirectory . DIRECTORY_SEPARATOR . 'c-like.sample.c');
            self::assertStringContainsString('token keyword Int', file_get_contents('c-like.lxs') ?: '');
            self::assertStringContainsString('"output": "generated/c-like"', file_get_contents('c-like.lxs.json') ?: '');
            self::assertStringContainsString('Created c-like.lxs', $output->stdout);
        } finally {
            chdir($previousDirectory);
        }
    }

    public function testInitCommandRefusesToOverwriteCLikeExampleProjectFiles(): void
    {
        mkdir($this->projectDirectory, 0777, true);
        file_put_contents($this->projectDirectory . DIRECTORY_SEPARATOR . 'c-like.lxs', 'already here');
        $previousDirectory = getcwd();
        self::assertIsString($previousDirectory);
        chdir($this->projectDirectory);

        try {
            $output = new BufferedOutput();
            $exitCode = ConsoleApplicationFactory::create()->run(['lsyn', 'init', 'c-like'], $output);

            self::assertSame(1, $exitCode);
            self::assertStringContainsString('already exists', $output->stderr);
            self::assertSame('already here', file_get_contents('c-like.lxs'));
        } finally {
            chdir($previousDirectory);
        }
    }

    public function testInitCommandRefusesToOverwriteExistingFile(): void
    {
        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run(['lsyn', 'init', $this->grammarFile], $output);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('already exists', $output->stderr);
    }

    public function testGenerateTokensCommandWritesTokenEnumAndMatchers(): void
    {
        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run([
            'lsyn',
            'generate:tokens',
            'examples/c-like.lxs',
            $this->generateDirectory,
            'CLikeTokenType',
        ], $output);

        self::assertSame(0, $exitCode);

        $enumFile = $this->generateDirectory . DIRECTORY_SEPARATOR . 'CLikeTokenType.php';
        self::assertFileExists($enumFile);

        $source = file_get_contents($enumFile) ?: '';
        self::assertStringContainsString('namespace Generated;', $source);
        self::assertStringContainsString('enum CLikeTokenType', $source);
        self::assertStringContainsString("#[Keyword('int')]", $source);
        self::assertStringContainsString("#[Symbol('+')]", $source);
        self::assertStringContainsString('use Lexicon\Lexer\Matchers\IdentifierTokenMatcher;', $source);
        self::assertStringContainsString('use Lexicon\Lexer\Matchers\WhitespaceTokenMatcher;', $source);
        self::assertStringContainsString('#[Identifier(IdentifierTokenMatcher::class)]', $source);
        self::assertStringContainsString('#[Trivia(WhitespaceTokenMatcher::class)]', $source);
        self::assertStringContainsString('#[EndOfFile]', $source);
        self::assertStringContainsString('#[Unknown]', $source);

        self::assertDirectoryDoesNotExist($this->generateDirectory . DIRECTORY_SEPARATOR . 'Matcher');
        self::assertStringContainsString('Generated', $output->stdout);
    }

    public function testGenerateAstCommandWritesSimpleActionNodes(): void
    {
        file_put_contents($this->grammarFile, <<<'GRAMMAR'
node Parameter(valueType: rule, name: token);
token keyword Int ::= 'int';
token Identifier ::= <IdentifierMatcher>;
token eof EndOfFile;
rule Type ::= Int;
#[start]
rule Parameter ::= valueType: Type name: Identifier => Parameter(valueType, name);
GRAMMAR);

        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run([
            'lsyn',
            'generate:ast',
            $this->grammarFile,
            $this->astDirectory,
        ], $output);

        self::assertSame(0, $exitCode);

        $file = $this->astDirectory . DIRECTORY_SEPARATOR . 'ParameterNode.php';
        self::assertFileExists($file);

        $source = file_get_contents($file) ?: '';
        self::assertStringContainsString('namespace Generated\\Ast;', $source);
        self::assertStringContainsString('final readonly class ParameterNode', $source);
        self::assertStringContainsString('public mixed $valueType', $source);
        self::assertStringContainsString('public Token $name', $source);
    }

    public function testGenerateAstCommandUsesResolvedNodeTypes(): void
    {
        file_put_contents($this->grammarFile, <<<'GRAMMAR'
node Type;
node TypeName : Type(name: token);
node StructTypeName : Type(name: token);
node Parameter(valueType: TypeRule, name: token);
token keyword Int ::= 'int';
token keyword Struct ::= 'struct';
token Identifier ::= <IdentifierMatcher>;
token eof EndOfFile;
rule TypeRule ::= name: Int => TypeName(name) | Struct name: Identifier => StructTypeName(name);
#[start]
rule ParameterRule ::= valueType: TypeRule name: Identifier => Parameter(valueType, name);
GRAMMAR);

        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run([
            'lsyn',
            'generate:ast',
            $this->grammarFile,
            $this->astDirectory,
        ], $output);

        self::assertSame(0, $exitCode);

        $typeSource = file_get_contents($this->astDirectory . DIRECTORY_SEPARATOR . 'TypeNode.php') ?: '';
        $typeNameSource = file_get_contents($this->astDirectory . DIRECTORY_SEPARATOR . 'TypeNameNode.php') ?: '';
        $parameterSource = file_get_contents($this->astDirectory . DIRECTORY_SEPARATOR . 'ParameterNode.php') ?: '';

        self::assertStringContainsString('interface TypeNode', $typeSource);
        self::assertStringContainsString('final readonly class TypeNameNode implements TypeNode', $typeNameSource);
        self::assertStringContainsString('public TypeNode $valueType', $parameterSource);
        self::assertStringContainsString('public Token $name', $parameterSource);
    }

    public function testGenerateParserCommandWritesRecursiveParserMethods(): void
    {
        file_put_contents($this->grammarFile, <<<'GRAMMAR'
node Parameter(valueType: rule, name: token, aliases: token);
token keyword Int ::= 'int';
token keyword Float ::= 'float';
token Identifier ::= <IdentifierMatcher>;
token eof EndOfFile;
rule Type ::= Int | Float;
#[start]
rule Parameter ::= valueType: Type name: Identifier aliases: Identifier* => Parameter(valueType, name, aliases);
GRAMMAR);

        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run([
            'lsyn',
            'generate:parser',
            $this->grammarFile,
            $this->generateDirectory,
            'DemoParser',
            'DemoTokenType',
        ], $output);

        self::assertSame(0, $exitCode);

        $file = $this->generateDirectory . DIRECTORY_SEPARATOR . 'DemoParser.php';
        self::assertFileExists($file);

        $source = file_get_contents($file) ?: '';
        self::assertStringContainsString('final class DemoParser', $source);
        self::assertStringContainsString('private TokenStream $tokens;', $source);
        self::assertStringContainsString('return $this->required($this->parseParameter(), \'Expected Parameter.\');', $source);
        self::assertStringContainsString('private function parseType(): mixed', $source);
        self::assertStringContainsString('return $this->tokens->oneOf(DemoTokenType::Int, DemoTokenType::Float);', $source);
        self::assertStringContainsString('private function parseParameter(): mixed', $source);
        self::assertStringContainsString('$valueType = $this->parseType();', $source);
        self::assertStringContainsString('$name = $this->tokens->match(DemoTokenType::Identifier);', $source);
        self::assertStringContainsString('$aliases = $this->tokens->many(DemoTokenType::Identifier);', $source);
        self::assertStringContainsString('return new Ast\\ParameterNode($valueType, $name, $aliases);', $source);
        self::assertStringNotContainsString('private function many(', $source);
        self::assertStringNotContainsString('private function optional(', $source);
        self::assertStringNotContainsString('private function oneOrMore(', $source);
    }

    public function testGenerateParserCommandWritesActionAlternatives(): void
    {
        file_put_contents($this->grammarFile, <<<'GRAMMAR'
node TypeName(name: token);
token keyword Int ::= 'int';
token keyword Float ::= 'float';
token eof EndOfFile;
#[start]
rule Type ::= name: Int => TypeName(name) | name: Float => TypeName(name);
GRAMMAR);

        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run([
            'lsyn',
            'generate:parser',
            $this->grammarFile,
            $this->generateDirectory,
            'DemoParser',
            'DemoTokenType',
        ], $output);

        self::assertSame(0, $exitCode);

        $file = $this->generateDirectory . DIRECTORY_SEPARATOR . 'DemoParser.php';
        self::assertFileExists($file);

        $source = file_get_contents($file) ?: '';
        self::assertStringContainsString('return $this->tokens->oneOf(' . PHP_EOL, $source);
        self::assertStringContainsString('fn (): mixed => (function () use (&$name): mixed {', $source);
        self::assertStringContainsString('$name = $this->tokens->match(DemoTokenType::Int);', $source);
        self::assertStringContainsString('return new Ast\\TypeNameNode($name);', $source);
        self::assertStringContainsString('$name = $this->tokens->match(DemoTokenType::Float);', $source);
    }

    public function testGenerateParserCommandWritesPlainSequenceAlternatives(): void
    {
        file_put_contents($this->grammarFile, <<<'GRAMMAR'
token symbol Dot ::= '.';
token symbol Arrow ::= '->';
token Identifier ::= <IdentifierMatcher>;
token eof EndOfFile;
#[start]
rule MemberSuffix ::= Dot Identifier | Arrow Identifier;
GRAMMAR);

        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run([
            'lsyn',
            'generate:parser',
            $this->grammarFile,
            $this->generateDirectory,
            'DemoParser',
            'DemoTokenType',
        ], $output);

        self::assertSame(0, $exitCode);

        $file = $this->generateDirectory . DIRECTORY_SEPARATOR . 'DemoParser.php';
        self::assertFileExists($file);

        $source = file_get_contents($file) ?: '';
        self::assertStringContainsString('$__part0 = $this->tokens->match(DemoTokenType::Dot);', $source);
        self::assertStringContainsString('$__part0 = $this->tokens->match(DemoTokenType::Arrow);', $source);
        self::assertStringContainsString('return $__part1;', $source);
    }

    public function testGenerateParserCommandExpandsTokenUnionTypes(): void
    {
        file_put_contents($this->grammarFile, <<<'GRAMMAR'
type BinaryOperator = Plus | Minus;
node Operator(op: BinaryOperator);
token symbol Plus ::= '+';
token symbol Minus ::= '-';
token eof EndOfFile;
#[start]
rule Operator ::= op: BinaryOperator => Operator(op);
GRAMMAR);

        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run([
            'lsyn',
            'generate:parser',
            $this->grammarFile,
            $this->generateDirectory,
            'DemoParser',
            'DemoTokenType',
        ], $output);

        self::assertSame(0, $exitCode);

        $file = $this->generateDirectory . DIRECTORY_SEPARATOR . 'DemoParser.php';
        self::assertFileExists($file);

        $source = file_get_contents($file) ?: '';
        self::assertStringContainsString('$op = $this->tokens->oneOf(DemoTokenType::Plus, DemoTokenType::Minus);', $source);
        self::assertStringContainsString('return new Ast\\OperatorNode($op);', $source);
    }

    public function testGenerateCommandUsesProjectConfig(): void
    {
        mkdir($this->projectDirectory, 0777, true);
        file_put_contents($this->projectDirectory . DIRECTORY_SEPARATOR . 'grammar.lxs', <<<'GRAMMAR'
node Parameter(valueType: rule, name: token);
token keyword Int ::= 'int';
token Identifier ::= <IdentifierMatcher>;
token eof EndOfFile;
rule Type ::= Int;
#[start]
rule Parameter ::= valueType: Type name: Identifier => Parameter(valueType, name);
GRAMMAR);
        file_put_contents($this->projectDirectory . DIRECTORY_SEPARATOR . 'lexicon-syntax.json', json_encode([
            'source' => 'grammar.lxs',
            'output' => 'generated',
            'tokenEnum' => 'ProjectTokenType',
            'parser' => 'ProjectParser',
        ], JSON_PRETTY_PRINT));

        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run([
            'lsyn',
            'generate',
            $this->projectDirectory . DIRECTORY_SEPARATOR . 'lexicon-syntax.json',
        ], $output);

        self::assertSame(0, $exitCode);

        $outputDirectory = $this->projectDirectory . DIRECTORY_SEPARATOR . 'generated';
        self::assertFileExists($outputDirectory . DIRECTORY_SEPARATOR . 'ProjectTokenType.php');
        self::assertFileExists($outputDirectory . DIRECTORY_SEPARATOR . 'ProjectParser.php');
        self::assertFileExists($outputDirectory . DIRECTORY_SEPARATOR . 'Ast' . DIRECTORY_SEPARATOR . 'ParameterNode.php');
        self::assertStringContainsString('Generated', $output->stdout);
    }

    public function testGenerateCommandWritesAllDeclaredAstNodesForCLikeExample(): void
    {
        $outputDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'generated' . DIRECTORY_SEPARATOR . 'c-like';

        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run([
            'lsyn',
            'generate',
            'examples/c-like',
        ], $output);

        self::assertSame(0, $exitCode);
        self::assertFileExists($outputDirectory . DIRECTORY_SEPARATOR . 'Ast' . DIRECTORY_SEPARATOR . 'ProgramNode.php');
        self::assertFileExists($outputDirectory . DIRECTORY_SEPARATOR . 'Ast' . DIRECTORY_SEPARATOR . 'TypeNameNode.php');
        self::assertFileExists($outputDirectory . DIRECTORY_SEPARATOR . 'Ast' . DIRECTORY_SEPARATOR . 'BinaryExpressionNode.php');
        self::assertFileExists($outputDirectory . DIRECTORY_SEPARATOR . 'Ast' . DIRECTORY_SEPARATOR . 'CallExpressionNode.php');
    }

    public function testParseCLikeCommandParsesSampleFile(): void
    {
        $output = new BufferedOutput();
        $exitCode = ConsoleApplicationFactory::create()->run([
            'lsyn',
            'parse:c-like',
            'examples/c-like.sample.c',
            'examples/c-like',
        ], $output);

        self::assertSame(0, $exitCode);
        $plainOutput = self::withoutAnsi($output->stdout);

        self::assertStringContainsString('ProgramNode', $plainOutput);
        self::assertStringContainsString('StructDeclarationNode', $plainOutput);
        self::assertStringContainsString('FunctionDeclarationNode', $plainOutput);
        self::assertStringContainsString('parameters: list', $plainOutput);
        self::assertStringContainsString('ParameterNode', $plainOutput);
        self::assertStringContainsString('BinaryExpressionNode', $plainOutput);
        self::assertStringContainsString('op: Plus "+"', $plainOutput);
        self::assertStringContainsString('name: Identifier "right"', $plainOutput);
        self::assertStringNotContainsString('parameters: true', $plainOutput);
        self::assertStringNotContainsString('value: true', $plainOutput);
    }

    private static function removeDirectory(string $directory): void
    {
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (is_dir($path)) {
                self::removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }

    private static function withoutAnsi(string $value): string
    {
        return preg_replace('/\033\[[0-9;]*m/', '', $value) ?? $value;
    }
}
