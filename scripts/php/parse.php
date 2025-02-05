<?php

declare(strict_types=1);

require_once __DIR__ . "/vendor/autoload.php";


class RemoveInterfaceVisitor extends \PhpParser\NodeVisitorAbstract {
    public function __construct(private string $interfaceName)
    {
    }

    public function enterNode(\PhpParser\Node $node): void
    {
        $fqdn = new \PhpParser\Node\Name\FullyQualified($this->interfaceName);

        // Überprüfen, ob es sich um eine Klasse handelt, die ein Interface implementiert
        if ($node instanceof \PhpParser\Node\Stmt\Class_ && !empty($node->implements)) {
            // Entferne das Interface
            foreach ($node->implements as $key => $interface) {
                if ($interface->toCodeString() === $fqdn->toCodeString()) {
                    unset($node->implements[$key]); // Interface entfernen
                }
            }
        }
    }
}

class AddInterfaceVisitor extends \PhpParser\NodeVisitorAbstract {
    public function __construct(
        private string $interfaceName,
    ) {}

    public function enterNode(\PhpParser\Node $node): void
    {
        $fqdn = new \PhpParser\Node\Name\FullyQualified($this->interfaceName);

        if ($node instanceof \PhpParser\Node\Stmt\Class_) {
            $interfaceNodeExists = false;
            $nodeName = $fqdn->toCodeString();

            foreach ($node->implements as $key => $interface) {
                if ($interface->toCodeString() === $nodeName) {
                    $interfaceNodeExists = true;
                }
            }
            if (!$interfaceNodeExists) {
                $node->implements[] = new \PhpParser\Node\Name($nodeName);
            }
        }
    }
}

$modelsDir = __DIR__ . '/../../php/Models/';

$finder = \Symfony\Component\Finder\Finder::create();

$finder->files()->name('*.php')->in($modelsDir);

foreach ($finder as $file) {
    $parser = (new \PhpParser\ParserFactory())->createForVersion(\PhpParser\PhpVersion::fromComponents(8, 3));

    try {
        $ast = $parser->parse($file->getContents());
    } catch (Error $error) {
        echo "Parse error: {$error->getMessage()}\n";
        return;
    }

    $traverser = new \PhpParser\NodeTraverser();

    $traverser->addVisitor(new RemoveInterfaceVisitor(\JsonSerializable::class));
    $traverser->addVisitor(new AddInterfaceVisitor('Tecsafe\OFCP\Events\OfcpEvent'));

    $modifiedStmts = $traverser->traverse($ast);

    $prettyPrinter = new \PhpParser\PrettyPrinter\Standard();
    $newCode = $prettyPrinter->prettyPrintFile($modifiedStmts);

    $fileObj = $file->openFile('w+');

    $fileObj->fwrite($newCode);

    echo $fileObj->getFilename() . ' done.' . PHP_EOL;
}
